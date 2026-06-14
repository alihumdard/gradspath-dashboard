<?php

namespace Modules\Payments\app\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\Auth\app\Models\User;
use Modules\Settings\app\Models\Mentor;

class StripeConnectService
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function ensureConnectedAccount(User $user): Mentor
    {
        $mentor = $user->mentor()->firstOrCreate([], [
            'user_id' => $user->id,
            'mentor_type' => 'graduate',
        ]);

        if (filled($mentor->stripe_account_id)) {
            return $mentor;
        }

        $account = $this->stripe->createConnectedAccount([
            'type' => 'express',
            'country' => (string) config('services.stripe.connect_country', 'US'),
            'email' => $user->email,
            'business_type' => 'individual',
            'capabilities' => [
                'transfers' => [
                    'requested' => true,
                ],
            ],
            'metadata' => [
                'mentor_id' => (string) $mentor->id,
                'user_id' => (string) $user->id,
            ],
        ]);

        $mentor->update([
            'stripe_account_id' => (string) Arr::get($account, 'id'),
        ]);

        return $mentor->fresh();
    }

    public function createHostedOnboardingLink(Mentor $mentor, string $refreshUrl, string $returnUrl, ?string $type = null): array
    {
        $linkType = $type ?? $this->defaultLinkType($mentor);

        try {
            return $this->stripe->createAccountLink($this->accountLinkPayload(
                $mentor,
                $refreshUrl,
                $returnUrl,
                $linkType
            ));
        } catch (RequestException $exception) {
            if ($this->isStaleConnectedAccountException($exception)) {
                $mentor = $this->replaceStaleConnectedAccount($mentor);

                return $this->stripe->createAccountLink($this->accountLinkPayload(
                    $mentor,
                    $refreshUrl,
                    $returnUrl,
                    'account_onboarding'
                ));
            }

            if ($linkType !== 'account_update') {
                throw $exception;
            }

            return $this->stripe->createAccountLink($this->accountLinkPayload(
                $mentor,
                $refreshUrl,
                $returnUrl,
                'account_onboarding'
            ));
        }
    }

    private function replaceStaleConnectedAccount(Mentor $mentor): Mentor
    {
        $staleAccountId = $mentor->stripe_account_id;
        $user = $mentor->loadMissing('user')->user;

        if (!$user) {
            throw new \RuntimeException('Cannot recreate Stripe account without a mentor user.');
        }

        Log::warning('Resetting stale Stripe connected account before onboarding retry.', [
            'mentor_id' => $mentor->id,
            'user_id' => $mentor->user_id,
            'stripe_account_id' => $staleAccountId,
        ]);

        $mentor->update([
            'stripe_account_id' => null,
            'payouts_enabled' => false,
            'stripe_onboarding_complete' => false,
        ]);

        return $this->ensureConnectedAccount($user);
    }

    private function isStaleConnectedAccountException(RequestException $exception): bool
    {
        $response = $exception->response;

        if (!$response || !in_array($response->status(), [400, 404], true)) {
            return false;
        }

        $message = strtolower((string) data_get($response->json(), 'error.message'));

        return str_contains($message, 'not connected to your platform')
            || str_contains($message, 'no such account')
            || str_contains($message, 'does not have access to account');
    }

    public function syncMentorFromStripeAccount(array $account): void
    {
        $accountId = (string) ($account['id'] ?? '');

        if ($accountId === '') {
            return;
        }

        $mentor = Mentor::query()->where('stripe_account_id', $accountId)->first();

        if (!$mentor) {
            return;
        }

        $currentlyDue = array_values(Arr::wrap(Arr::get($account, 'requirements.currently_due')));
        $eventuallyDue = array_values(Arr::wrap(Arr::get($account, 'requirements.eventually_due')));
        $payoutsEnabled = (bool) Arr::get($account, 'payouts_enabled', false);
        $detailsSubmitted = (bool) Arr::get($account, 'details_submitted', false);

        $mentor->update([
            'payouts_enabled' => $payoutsEnabled,
            'stripe_onboarding_complete' => $detailsSubmitted || ($payoutsEnabled && $currentlyDue === [] && $eventuallyDue === []),
        ]);
    }

    public function defaultLinkType(Mentor $mentor): string
    {
        if ($mentor->payouts_enabled || $mentor->stripe_onboarding_complete) {
            return 'account_update';
        }

        return 'account_onboarding';
    }

    public function buttonLabel(Mentor $mentor): string
    {
        if ($mentor->payouts_enabled || $mentor->stripe_onboarding_complete) {
            return 'Update payout details';
        }

        if (filled($mentor->stripe_account_id)) {
            return 'Continue payout setup';
        }

        return 'Enable Payouts';
    }

    public function emptyPayoutStatus(): array
    {
        return [
            'payouts_enabled' => false,
            'stripe_onboarding_complete' => false,
            'status_label' => 'Not enabled',
            'summary_label' => 'Not enabled yet',
            'button_label' => 'Enable Payouts',
        ];
    }

    public function payoutStatus(Mentor $mentor): array
    {
        return [
            'payouts_enabled' => (bool) $mentor->payouts_enabled,
            'stripe_onboarding_complete' => (bool) $mentor->stripe_onboarding_complete,
            'status_label' => $mentor->payouts_enabled ? 'Enabled' : 'Not enabled',
            'summary_label' => $mentor->payouts_enabled ? 'Enabled' : 'Not enabled yet',
            'button_label' => $this->buttonLabel($mentor),
        ];
    }

    private function accountLinkPayload(Mentor $mentor, string $refreshUrl, string $returnUrl, string $type): array
    {
        $payload = [
            'account' => $mentor->stripe_account_id,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => $type,
        ];

        if ($type === 'account_onboarding') {
            $payload['collection_options'] = [
                'fields' => 'eventually_due',
            ];
        }

        return $payload;
    }
}

<?php

namespace Modules\Payments\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Payments\app\Models\StripeWebhook;

class StripeWebhookService
{
    private const HANDLED_EVENTS = [
        'checkout.session.completed',
        'account.updated',
        'v2.core.account_link.returned',
    ];

    public function __construct(
        private readonly BookingCheckoutService $bookingCheckout,
        private readonly CreditService $creditService,
        private readonly StripeConnectService $connect,
        private readonly StripeClient $stripe
    ) {}

    public function process(array $payload): StripeWebhook
    {
        $eventId = (string) ($payload['id'] ?? '');
        $eventType = (string) ($payload['type'] ?? 'unknown');

        if ($eventId === '') {
            throw new \InvalidArgumentException('Stripe event id is required.');
        }

        if (! in_array($eventType, self::HANDLED_EVENTS, true)) {
            return new StripeWebhook([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => [],
                'processed' => true,
                'received_at' => now(),
                'processed_at' => now(),
            ]);
        }

        $webhook = $this->reserveWebhook($eventId, $eventType, $payload);

        if ($webhook->processed) {
            return $webhook;
        }

        $this->processEvent($eventType, $payload, $eventId);

        return $this->markProcessed($webhook);
    }

    private function reserveWebhook(string $eventId, string $eventType, array $payload): StripeWebhook
    {
        return DB::transaction(function () use ($eventId, $eventType, $payload) {
            $webhook = StripeWebhook::query()
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if ($webhook) {
                return $webhook;
            }

            return StripeWebhook::create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => $payload,
                'processed' => false,
                'received_at' => now(),
            ]);
        }, 5);
    }

    private function markProcessed(StripeWebhook $webhook): StripeWebhook
    {
        return DB::transaction(function () use ($webhook) {
            $webhook = StripeWebhook::query()
                ->whereKey($webhook->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $webhook->processed) {
                $webhook->forceFill([
                    'processed' => true,
                    'processed_at' => now(),
                ])->save();
            }

            return $webhook;
        }, 5);
    }

    private function processEvent(string $eventType, array $payload, string $eventId): void
    {
        if ($eventType === 'checkout.session.completed') {
            $paymentType = (string) data_get($payload, 'data.object.metadata.payment_type');

            if ($paymentType === 'booking') {
                $this->bookingCheckout->finalizePaidBookingFromStripeSession(
                    (array) data_get($payload, 'data.object', []),
                    $eventId
                );
            } elseif ($paymentType === 'credits') {
                $userId = (int) data_get($payload, 'data.object.metadata.user_id');
                $credits = (int) data_get($payload, 'data.object.metadata.credits', 0);

                if ($userId > 0 && $credits > 0) {
                    $user = User::query()->find($userId);
                    if ($user) {
                        $this->creditService->purchase(
                            $user,
                            $credits,
                            (string) data_get($payload, 'data.object.payment_intent'),
                            $eventId
                        );
                    }
                }
            }
        } elseif ($eventType === 'account.updated') {
            $this->connect->syncMentorFromStripeAccount(
                (array) data_get($payload, 'data.object', [])
            );
        } elseif ($eventType === 'v2.core.account_link.returned') {
            $accountId = (string) data_get($payload, 'data.account_id');

            if ($accountId !== '') {
                $this->connect->syncMentorFromStripeAccount(
                    $this->stripe->retrieveConnectedAccount($accountId)
                );
            }
        }
    }
}

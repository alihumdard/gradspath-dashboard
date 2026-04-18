<?php

namespace Modules\Payments\app\Services;

use Modules\Auth\app\Models\User;

class CreditCheckoutService
{
    public function __construct(
        private readonly CreditService $credits,
        private readonly StripeClient $stripe,
    ) {}

    public function createCheckoutSession(User $user, int $credits, ?string $program = null): array
    {
        if ($credits !== 5) {
            throw new \RuntimeException('Only the 5-credit pack is available right now.');
        }

        $program = in_array($program, ['mba', 'law', 'therapy'], true) ? $program : 'mba';
        $amount = 20000;

        return $this->stripe->createCheckoutSession([
            'mode' => 'payment',
            'success_url' => $this->successUrl(),
            'cancel_url' => $this->cancelUrl(),
            'customer_email' => $user->email,
            'metadata' => [
                'payment_type' => 'credits',
                'user_id' => (string) $user->id,
                'credits' => (string) $credits,
                'office_hours_program' => $program,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $amount,
                    'product_data' => [
                        'name' => '5 Credit Pack',
                        'description' => sprintf(
                            'One-time credit purchase for Office Hours access (%s)',
                            strtoupper($program)
                        ),
                    ],
                ],
            ]],
        ]);
    }

    public function completeSuccessfulCheckout(User $user, string $checkoutSessionId): array
    {
        $session = $this->stripe->retrieveCheckoutSession($checkoutSessionId);

        if ((int) data_get($session, 'metadata.user_id') !== (int) $user->id) {
            throw new \RuntimeException('This checkout session does not belong to the current student.');
        }

        if ((string) data_get($session, 'payment_status') !== 'paid') {
            throw new \RuntimeException('Stripe payment is not marked as paid.');
        }

        $credits = (int) data_get($session, 'metadata.credits', 0);
        if ($credits <= 0) {
            throw new \RuntimeException('No credit amount was attached to this checkout session.');
        }

        $wallet = $this->credits->purchase(
            $user,
            $credits,
            (string) data_get($session, 'payment_intent.id', data_get($session, 'payment_intent')),
            null
        );

        return [
            'session' => $session,
            'wallet' => $wallet,
            'credits' => $credits,
        ];
    }

    private function successUrl(): string
    {
        return route('student.store.success').'?session_id={CHECKOUT_SESSION_ID}';
    }

    private function cancelUrl(): string
    {
        return route('student.store', ['checkout' => 'cancelled']);
    }
}

<?php

namespace Modules\Payments\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Payments\app\Models\StripeWebhook;

class StripeWebhookService
{
    public function __construct(
        private readonly BookingCheckoutService $bookingCheckout,
        private readonly CreditService $creditService,
        private readonly StripeConnectService $connect
    ) {}

    public function process(array $payload): StripeWebhook
    {
        $eventId = (string) ($payload['id'] ?? '');
        $eventType = (string) ($payload['type'] ?? 'unknown');

        if ($eventId === '') {
            throw new \InvalidArgumentException('Stripe event id is required.');
        }

        return DB::transaction(function () use ($eventId, $eventType, $payload) {
            $existing = StripeWebhook::query()->where('event_id', $eventId)->lockForUpdate()->first();

            if ($existing && $existing->processed) {
                return $existing;
            }

            $webhook = $existing ?? StripeWebhook::create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => $payload,
                'processed' => false,
                'received_at' => now(),
            ]);

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
            }

            $webhook->processed = true;
            $webhook->processed_at = now();
            $webhook->save();

            return $webhook;
        });
    }
}

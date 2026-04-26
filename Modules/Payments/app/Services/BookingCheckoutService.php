<?php

namespace Modules\Payments\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\Bookings\app\Services\BookingService;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\Payments\app\Models\BookingPayment;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class BookingCheckoutService
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly StripeClient $stripe,
        private readonly MentorPayoutService $payouts,
    ) {}

    public function createCheckoutSession(User $booker, array $data): BookingPayment
    {
        $service = ServiceConfig::query()->findOrFail((int) $data['service_config_id']);
        $mentor = Mentor::query()->findOrFail((int) $data['mentor_id']);
        $sessionType = (string) ($data['session_type'] ?? '1on1');
        $portalContext = (string) ($data['portal_context'] ?? 'student');
        $amount = $this->amountFor($service, $sessionType);

        if ($portalContext === 'mentor' && $sessionType !== '1on1') {
            throw new \RuntimeException('Mentor-to-mentor booking currently supports standard 1 on 1 sessions only.');
        }

        if ((bool) $service->is_office_hours || $sessionType === 'office_hours') {
            throw new \RuntimeException('Office Hours must be booked using credits.');
        }

        if ($amount <= 0) {
            throw new \RuntimeException('This booking does not require Stripe checkout.');
        }

        $this->assertSelectionIsBookable($mentor, $service, $sessionType, $data, $booker);

        $requestPayload = [
            'mentor_id' => (int) $mentor->id,
            'service_config_id' => (int) $service->id,
            'session_type' => $sessionType,
            'mentor_availability_slot_id' => $data['mentor_availability_slot_id'] ?? null,
            'office_hour_session_id' => $data['office_hour_session_id'] ?? null,
            'meeting_type' => (string) ($data['meeting_type'] ?? 'zoom'),
            'guest_participants' => array_values($data['guest_participants'] ?? []),
            'portal_context' => $portalContext,
        ];

        $payment = BookingPayment::query()->create([
            'user_id' => $booker->id,
            'mentor_id' => $mentor->id,
            'service_config_id' => $service->id,
            'mentor_availability_slot_id' => $requestPayload['mentor_availability_slot_id'],
            'office_hour_session_id' => $requestPayload['office_hour_session_id'],
            'session_type' => $sessionType,
            'meeting_type' => $requestPayload['meeting_type'],
            'amount' => $amount,
            'currency' => 'USD',
            'guest_participants' => $requestPayload['guest_participants'],
            'request_payload' => $requestPayload,
            'status' => 'initiated',
        ]);

        $session = $this->stripe->createCheckoutSession([
            'mode' => 'payment',
            'success_url' => $this->successUrl($portalContext),
            'cancel_url' => $this->cancelUrl($mentor, $portalContext),
            'client_reference_id' => (string) $payment->id,
            'customer_email' => $booker->email,
            'metadata' => [
                'payment_type' => 'booking',
                'booking_payment_id' => (string) $payment->id,
                'user_id' => (string) $booker->id,
                'portal_context' => $portalContext,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => (int) round($amount * 100),
                    'product_data' => [
                        'name' => $service->service_name,
                        'description' => sprintf(
                            '%s with %s',
                            $this->labelFor($sessionType),
                            $mentor->user?->name ?? 'Mentor'
                        ),
                    ],
                ],
            ]],
        ]);

        $payment->forceFill([
            'stripe_checkout_session_id' => (string) ($session['id'] ?? ''),
            'checkout_url' => (string) ($session['url'] ?? ''),
        ])->save();

        return $payment->fresh();
    }

    public function completeSuccessfulCheckout(User $booker, string $checkoutSessionId): Booking
    {
        $payment = BookingPayment::query()
            ->where('stripe_checkout_session_id', $checkoutSessionId)
            ->first();

        $session = $this->stripe->retrieveCheckoutSession($checkoutSessionId);

        if (!$payment) {
            $paymentId = (int) data_get($session, 'metadata.booking_payment_id');
            $payment = BookingPayment::query()->findOrFail($paymentId);
        }

        if ((int) $payment->user_id !== (int) $booker->id) {
            throw new \RuntimeException('This checkout session does not belong to the current user.');
        }

        return $this->finalizePaidBooking($payment, $session);
    }

    public function finalizePaidBookingFromStripeSession(array $session, ?string $eventId = null): ?Booking
    {
        $paymentId = (int) data_get($session, 'metadata.booking_payment_id');
        if ($paymentId <= 0) {
            return null;
        }

        $payment = BookingPayment::query()->find($paymentId);
        if (!$payment) {
            return null;
        }

        return $this->finalizePaidBooking($payment, $session, $eventId);
    }

    private function finalizePaidBooking(BookingPayment $payment, array $session, ?string $eventId = null): Booking
    {
        return DB::transaction(function () use ($payment, $session, $eventId) {
            $payment = BookingPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->booking_id) {
                $booking = Booking::query()->findOrFail($payment->booking_id);
                $this->payouts->recordBookingEarning($booking, $payment);

                return $booking;
            }

            if ((string) data_get($session, 'payment_status') !== 'paid') {
                throw new \RuntimeException('Stripe payment is not marked as paid.');
            }

            $booker = User::query()->findOrFail($payment->user_id);

            $payment->forceFill([
                'stripe_checkout_session_id' => (string) data_get($session, 'id', $payment->stripe_checkout_session_id),
                'stripe_payment_intent_id' => (string) data_get($session, 'payment_intent.id', data_get($session, 'payment_intent')),
                'stripe_event_id' => $eventId,
                'status' => 'paid',
                'payment_completed_at' => now(),
                'failure_reason' => null,
            ])->save();

            try {
                $booking = $this->bookings->createBooking(
                    $booker,
                    $payment->request_payload,
                    ['charge_credits' => false]
                );
            } catch (\Throwable $exception) {
                $payment->forceFill([
                    'status' => 'failed',
                    'failure_reason' => $exception->getMessage(),
                ])->save();

                throw $exception;
            }

            $payment->forceFill([
                'booking_id' => $booking->id,
                'status' => 'booking_created',
                'booking_created_at' => now(),
            ])->save();

            $this->payouts->recordBookingEarning($booking, $payment);

            return $booking;
        });
    }

    private function assertSelectionIsBookable(Mentor $mentor, ServiceConfig $service, string $sessionType, array $data, User $booker): void
    {
        if ((int) ($mentor->user_id ?? 0) === (int) $booker->id) {
            throw new \RuntimeException('You cannot book a meeting with yourself.');
        }

        $offersService = $mentor->services()
            ->where('services_config.id', $service->id)
            ->where('services_config.is_active', true)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$offersService) {
            throw new \RuntimeException('This mentor does not currently offer the selected service.');
        }

        if ($sessionType === 'office_hours') {
            $session = OfficeHourSession::query()->findOrFail((int) ($data['office_hour_session_id'] ?? 0));

            if ((int) ($session->schedule?->mentor_id ?? 0) !== (int) $mentor->id) {
                throw new \RuntimeException('This office-hours session does not belong to the selected mentor.');
            }

            return;
        }

        $slot = MentorAvailabilitySlot::query()->findOrFail((int) ($data['mentor_availability_slot_id'] ?? 0));

        if ((int) $slot->mentor_id !== (int) $mentor->id) {
            throw new \RuntimeException('This time slot does not belong to the selected mentor.');
        }

        if ($slot->service_config_id !== null && (int) $slot->service_config_id !== (int) $service->id) {
            throw new \RuntimeException('This time slot does not match the selected service.');
        }

        if ($slot->session_type !== $sessionType) {
            throw new \RuntimeException('This time slot does not match the selected meeting size.');
        }

        if ($slot->service_config_id === null && $sessionType !== '1on1') {
            throw new \RuntimeException('This time slot is only available for 1 on 1 bookings.');
        }

        if (!$slot->is_active || $slot->is_blocked || $slot->is_booked) {
            throw new \RuntimeException('This time slot is no longer available.');
        }

        if ($slot->bookings()->whereIn('status', ['pending', 'confirmed'])->exists()) {
            throw new \RuntimeException('This time slot has already been reserved.');
        }
    }

    private function amountFor(ServiceConfig $service, string $sessionType): float
    {
        return match ($sessionType) {
            '1on3' => (float) ($service->price_1on3_total ?? ((float) ($service->price_1on3_per_person ?? 0) * 3)),
            '1on5' => (float) ($service->price_1on5_total ?? ((float) ($service->price_1on5_per_person ?? 0) * 5)),
            'office_hours' => 0.0,
            default => (float) ($service->price_1on1 ?? 0),
        };
    }

    private function successUrl(string $portalContext): string
    {
        return (string) (config('services.stripe.booking_success_url')
            ?: route("{$portalContext}.bookings.checkout.success").'?session_id={CHECKOUT_SESSION_ID}');
    }

    private function cancelUrl(Mentor $mentor, string $portalContext): string
    {
        return (string) (config('services.stripe.booking_cancel_url')
            ?: route("{$portalContext}.mentor.book", ['id' => $mentor->id, 'checkout' => 'cancelled']));
    }

    private function labelFor(string $sessionType): string
    {
        return match ($sessionType) {
            '1on3' => '1 on 3 session',
            '1on5' => '1 on 5 session',
            default => '1 on 1 session',
        };
    }
}

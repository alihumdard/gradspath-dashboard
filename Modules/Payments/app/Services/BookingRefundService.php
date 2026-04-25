<?php

namespace Modules\Payments\app\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\BookingPayment;
use Modules\Payments\app\Models\BookingRefund;
use Modules\Payments\app\Models\MentorPayout;

class BookingRefundService
{
    public function __construct(
        private readonly CreditService $credits,
        private readonly StripeClient $stripe,
    ) {}

    public function refundCancelledBooking(Booking $booking): ?BookingRefund
    {
        $booking = Booking::query()->findOrFail($booking->id);

        if (! in_array((string) $booking->status, ['cancelled', 'cancelled_pending_refund'], true)) {
            return null;
        }

        $payment = BookingPayment::query()
            ->where('booking_id', $booking->id)
            ->latest('id')
            ->first();

        if ($payment && (float) $payment->amount > 0) {
            return $this->refundStripeBooking($booking, $payment);
        }

        if ((int) $booking->credits_charged > 0) {
            return $this->refundCreditBooking($booking);
        }

        return null;
    }

    private function refundCreditBooking(Booking $booking): BookingRefund
    {
        return DB::transaction(function () use ($booking) {
            $refund = $this->existingOrNewRefund($booking, BookingRefund::TYPE_CREDITS);

            if ($refund->status === BookingRefund::STATUS_SUCCEEDED) {
                return $refund;
            }

            $refund->fill([
                'student_id' => $booking->student_id,
                'type' => BookingRefund::TYPE_CREDITS,
                'status' => BookingRefund::STATUS_PENDING,
                'amount' => 0,
                'credits' => (int) $booking->credits_charged,
                'currency' => strtoupper((string) ($booking->currency ?: 'USD')),
                'requested_at' => $refund->requested_at ?? now(),
                'failure_reason' => null,
                'failed_at' => null,
            ])->save();

            $this->credits->refund(
                $booking->student,
                (int) $booking->credits_charged,
                $booking,
                null,
                'Automatic refund for cancelled booking'
            );

            $refund->forceFill([
                'status' => BookingRefund::STATUS_SUCCEEDED,
                'succeeded_at' => now(),
            ])->save();

            return $refund->fresh();
        });
    }

    private function refundStripeBooking(Booking $booking, BookingPayment $payment): BookingRefund
    {
        $payout = MentorPayout::query()
            ->where('booking_id', $booking->id)
            ->latest('id')
            ->first();

        $refund = DB::transaction(function () use ($booking, $payment, $payout) {
            $refund = $this->existingOrNewRefund($booking, BookingRefund::TYPE_STRIPE);

            if ($refund->status === BookingRefund::STATUS_SUCCEEDED) {
                return $refund;
            }

            $refund->fill([
                'booking_payment_id' => $payment->id,
                'mentor_payout_id' => $payout?->id,
                'student_id' => $booking->student_id,
                'type' => BookingRefund::TYPE_STRIPE,
                'status' => BookingRefund::STATUS_PENDING,
                'amount' => (float) $payment->amount,
                'credits' => 0,
                'currency' => strtoupper((string) ($payment->currency ?: $booking->currency ?: 'USD')),
                'requested_at' => $refund->requested_at ?? now(),
                'failure_reason' => null,
                'failed_at' => null,
            ])->save();

            return $refund->fresh();
        });

        try {
            if ($this->needsTransferReversal($payout) && ! $refund->stripe_transfer_reversal_id) {
                $reversal = $this->stripe->createTransferReversal(
                    (string) $payout->stripe_transfer_id,
                    [
                        'amount' => (int) round((float) $payout->mentor_share_amount * 100),
                        'metadata' => [
                            'booking_id' => (string) $booking->id,
                            'mentor_payout_id' => (string) $payout->id,
                            'booking_refund_id' => (string) $refund->id,
                        ],
                    ],
                );

                DB::transaction(function () use ($refund, $payout, $reversal) {
                    $refund->forceFill([
                        'stripe_transfer_reversal_id' => (string) ($reversal['id'] ?? ''),
                    ])->save();

                    $payout->forceFill([
                        'status' => MentorPayout::STATUS_REVERSED,
                        'failure_reason' => null,
                        'failed_at' => null,
                    ])->save();
                });
            }

            if (! $refund->fresh()->stripe_refund_id) {
                $stripeRefund = $this->stripe->createRefund([
                    'payment_intent' => $payment->stripe_payment_intent_id,
                    'amount' => (int) round((float) $payment->amount * 100),
                    'reason' => 'requested_by_customer',
                    'metadata' => [
                        'booking_id' => (string) $booking->id,
                        'booking_payment_id' => (string) $payment->id,
                        'booking_refund_id' => (string) $refund->id,
                    ],
                ]);

                $refund->forceFill([
                    'stripe_refund_id' => (string) ($stripeRefund['id'] ?? ''),
                    'status' => BookingRefund::STATUS_SUCCEEDED,
                    'succeeded_at' => now(),
                    'failure_reason' => null,
                    'failed_at' => null,
                ])->save();
            }
        } catch (RequestException $exception) {
            return $this->markForAdminReview(
                $booking,
                $refund,
                (string) data_get($exception->response?->json(), 'error.message', $exception->getMessage())
            );
        } catch (\Throwable $exception) {
            return $this->markForAdminReview($booking, $refund, $exception->getMessage());
        }

        return $refund->fresh();
    }

    private function existingOrNewRefund(Booking $booking, string $type): BookingRefund
    {
        return BookingRefund::query()
            ->lockForUpdate()
            ->firstOrNew(['booking_id' => $booking->id], [
                'student_id' => $booking->student_id,
                'type' => $type,
            ]);
    }

    private function needsTransferReversal(?MentorPayout $payout): bool
    {
        return $payout
            && $payout->status === MentorPayout::STATUS_TRANSFERRED
            && filled($payout->stripe_transfer_id);
    }

    private function markForAdminReview(Booking $booking, BookingRefund $refund, string $reason): BookingRefund
    {
        DB::transaction(function () use ($booking, $refund, $reason) {
            $booking->forceFill([
                'status' => 'cancelled_pending_refund',
            ])->save();

            $refund->forceFill([
                'status' => BookingRefund::STATUS_REQUIRES_ADMIN_REVIEW,
                'failure_reason' => $reason,
                'failed_at' => now(),
            ])->save();
        });

        return $refund->fresh();
    }
}

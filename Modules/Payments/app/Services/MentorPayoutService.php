<?php

namespace Modules\Payments\app\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\BookingPayment;
use Modules\Payments\app\Models\MentorPayout;

class MentorPayoutService
{
    public function __construct(
        private readonly MentorPayoutCalculator $calculator,
        private readonly StripeClient $stripe,
    ) {}

    public function recordBookingEarning(Booking $booking, BookingPayment $payment): ?MentorPayout
    {
        if ((float) $payment->amount <= 0 || $booking->session_type === 'office_hours') {
            return null;
        }

        return DB::transaction(function () use ($booking, $payment) {
            $booking->loadMissing('mentor');
            $payout = MentorPayout::query()
                ->lockForUpdate()
                ->firstOrNew(['booking_id' => $booking->id]);

            $calculation = $this->calculator->forBooking($booking, $payment);

            $payout->fill([
                'mentor_id' => $booking->mentor_id,
                'booking_payment_id' => $payment->id,
                'student_id' => $booking->student_id,
                'stripe_account_id' => $booking->mentor?->stripe_account_id,
                'amount' => $calculation['mentor_share_amount'],
                'gross_amount' => $calculation['gross_amount'],
                'mentor_share_amount' => $calculation['mentor_share_amount'],
                'platform_fee_amount' => $calculation['platform_fee_amount'],
                'currency' => $calculation['currency'],
                'calculation_rule' => $calculation['calculation_rule'],
            ]);

            if (! in_array((string) $payout->status, [
                MentorPayout::STATUS_TRANSFERRED,
                MentorPayout::STATUS_PAID_OUT,
                MentorPayout::STATUS_REVERSED,
            ], true)) {
                $payout->status = MentorPayout::STATUS_PENDING_RELEASE;
                $payout->eligible_at = null;
                $payout->failure_reason = null;
                $payout->failed_at = null;
            }

            $payout->save();

            return $payout->fresh();
        });
    }

    public function releaseForCompletedBooking(Booking $booking): ?MentorPayout
    {
        if ($booking->status !== 'completed') {
            return null;
        }

        return DB::transaction(function () use ($booking) {
            $booking->loadMissing('mentor');

            $payout = MentorPayout::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->first();

            if (! $payout) {
                return null;
            }

            if (in_array((string) $payout->status, [
                MentorPayout::STATUS_TRANSFERRED,
                MentorPayout::STATUS_PAID_OUT,
                MentorPayout::STATUS_REVERSED,
            ], true)) {
                return $payout;
            }

            $payout->eligible_at = $payout->eligible_at ?? ($booking->completed_at ?? now());
            $payout->stripe_account_id = $booking->mentor?->stripe_account_id ?: $payout->stripe_account_id;

            if (! $booking->mentor?->stripe_account_id || ! $booking->mentor?->payouts_enabled) {
                $payout->status = MentorPayout::STATUS_READY;
                $payout->save();

                return $payout->fresh();
            }

            return $this->attemptTransfer($payout, $booking);
        });
    }

    public function reverseForCancelledBooking(Booking $booking): ?MentorPayout
    {
        return DB::transaction(function () use ($booking) {
            $payout = MentorPayout::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->first();

            if (! $payout || in_array((string) $payout->status, [
                MentorPayout::STATUS_TRANSFERRED,
                MentorPayout::STATUS_PAID_OUT,
                MentorPayout::STATUS_REVERSED,
            ], true)) {
                return $payout;
            }

            $payout->status = MentorPayout::STATUS_REVERSED;
            $payout->failure_reason = 'Booking cancelled before payout release.';
            $payout->failed_at = now();
            $payout->save();

            return $payout->fresh();
        });
    }

    public function retryEligiblePayouts(int $limit = 50): int
    {
        $processed = 0;
        $retryLimit = max((int) config('payments.mentor_payout_retry_limit', 5), 1);

        MentorPayout::query()
            ->with(['booking.mentor'])
            ->whereIn('status', [
                MentorPayout::STATUS_READY,
                MentorPayout::STATUS_FAILED,
            ])
            ->where('attempt_count', '<', $retryLimit)
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (MentorPayout $payout) use (&$processed) {
                $booking = $payout->booking;

                if (! $booking || $booking->status !== 'completed') {
                    return;
                }

                $this->releaseForCompletedBooking($booking);
                $processed++;
            });

        return $processed;
    }

    private function attemptTransfer(MentorPayout $payout, Booking $booking): MentorPayout
    {
        $payout->attempt_count = (int) $payout->attempt_count + 1;
        $payout->last_attempt_at = now();
        $payout->save();

        try {
            $transfer = $this->stripe->createTransfer([
                'amount' => (int) round((float) $payout->mentor_share_amount * 100),
                'currency' => strtolower((string) $payout->currency),
                'destination' => $payout->stripe_account_id,
                'transfer_group' => 'booking_'.$booking->id,
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'booking_payment_id' => (string) $payout->booking_payment_id,
                    'mentor_id' => (string) $payout->mentor_id,
                ],
            ]);

            $payout->forceFill([
                'status' => MentorPayout::STATUS_TRANSFERRED,
                'stripe_transfer_id' => (string) ($transfer['id'] ?? ''),
                'stripe_balance_transaction_id' => (string) ($transfer['balance_transaction'] ?? ''),
                'transferred_at' => now(),
                'payout_date' => now(),
                'failure_reason' => null,
                'failed_at' => null,
            ])->save();
        } catch (RequestException $exception) {
            $response = $exception->response;
            $payout->forceFill([
                'status' => MentorPayout::STATUS_FAILED,
                'failure_reason' => (string) data_get($response?->json(), 'error.message', $exception->getMessage()),
                'failed_at' => now(),
            ])->save();
        } catch (\Throwable $exception) {
            $payout->forceFill([
                'status' => MentorPayout::STATUS_FAILED,
                'failure_reason' => $exception->getMessage(),
                'failed_at' => now(),
            ])->save();
        }

        return $payout->fresh();
    }
}

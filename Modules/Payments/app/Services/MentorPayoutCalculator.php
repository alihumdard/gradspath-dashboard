<?php

namespace Modules\Payments\app\Services;

use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\BookingPayment;

class MentorPayoutCalculator
{
    public function forBooking(Booking $booking, BookingPayment $payment): array
    {
        $grossAmount = round((float) $payment->amount, 2);
        $percent = $this->mentorPercent();
        $mentorShare = round($grossAmount * ($percent / 100), 2);
        $platformFee = round($grossAmount - $mentorShare, 2);

        return [
            'gross_amount' => $grossAmount,
            'mentor_share_amount' => $mentorShare,
            'platform_fee_amount' => $platformFee,
            'currency' => strtoupper((string) ($payment->currency ?: $booking->currency ?: config('app.currency', 'USD'))),
            'calculation_rule' => [
                'type' => 'percent_of_gross',
                'mentor_percent' => $percent,
            ],
        ];
    }

    private function mentorPercent(): float
    {
        $percent = (float) config('payments.mentor_payout_percent_default', 70);

        return min(max($percent, 0), 100);
    }
}

<?php

namespace Modules\Bookings\app\Services;

use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Services\MentorPayoutService;

class BookingOutcomeService
{
    public function __construct(private readonly MentorPayoutService $payouts) {}

    public function update(Booking $booking, User $admin, array $data): Booking
    {
        $booking->fill([
            'session_outcome' => $data['session_outcome'],
            'session_outcome_note' => $data['session_outcome_note'] ?? $booking->session_outcome_note,
            'completion_source' => $data['completion_source'] ?? 'manual',
            'completed_at' => $booking->completed_at ?? now(),
        ]);

        if ($booking->status !== 'completed') {
            $booking->status = 'completed';
        }

        $booking->save();

        $this->payouts->releaseForCompletedBooking($booking);

        return $booking->fresh();
    }
}

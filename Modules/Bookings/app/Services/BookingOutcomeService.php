<?php

namespace Modules\Bookings\app\Services;

use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;

class BookingOutcomeService
{
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

        return $booking->fresh();
    }
}

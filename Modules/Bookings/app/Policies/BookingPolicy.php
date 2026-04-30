<?php

namespace Modules\Bookings\app\Policies;

use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return (int) $booking->student_id === (int) $user->id
            || $booking->hasParticipantUser($user)
            || (int) $booking->mentor?->user_id === (int) $user->id
            || $user->hasRole('admin');
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return (int) $booking->student_id === (int) $user->id
            || $booking->participants()->wherePivot('is_primary', true)->where('users.id', $user->id)->exists()
            || (int) ($booking->mentor?->user_id ?? 0) === (int) $user->id
            || $user->hasRole('admin');
    }
}

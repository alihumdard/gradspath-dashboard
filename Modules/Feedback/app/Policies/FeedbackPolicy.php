<?php

namespace Modules\Feedback\app\Policies;

use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Models\Feedback;

class FeedbackPolicy
{
    public function create(User $user, Booking $booking): bool
    {
        return (int) $booking->student_id === (int) $user->id
            && $booking->status === 'completed';
    }

    public function update(User $user, Feedback $feedback): bool
    {
        return $user->hasRole('admin') || (int) $feedback->student_id === (int) $user->id;
    }

    public function delete(User $user, Feedback $feedback): bool
    {
        return $user->hasRole('admin') || (int) $feedback->student_id === (int) $user->id;
    }
}

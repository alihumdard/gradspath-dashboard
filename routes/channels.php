<?php

use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('booking.{bookingId}', function ($user, $bookingId) {
    $booking = Booking::query()
        ->with('mentor:id,user_id')
        ->find($bookingId);

    if (!$booking) {
        return false;
    }

    if ((int) $booking->student_id === (int) $user->id) {
        return true;
    }

    $mentor = Mentor::query()->where('user_id', $user->id)->first();

    return $mentor && (int) $booking->mentor_id === (int) $mentor->id;
});

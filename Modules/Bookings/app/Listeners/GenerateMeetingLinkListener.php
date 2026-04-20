<?php

namespace Modules\Bookings\app\Listeners;

use Modules\Bookings\app\Events\BookingCreated;
use Modules\Bookings\app\Jobs\SendBookingConfirmationJob;

class GenerateMeetingLinkListener
{
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking;

        if (!$booking->meeting_link) {
            $booking->meeting_link = 'https://meet.gradspath.test/session/' . $booking->id;
            $booking->save();
        }

        SendBookingConfirmationJob::dispatch($booking->id);
    }
}

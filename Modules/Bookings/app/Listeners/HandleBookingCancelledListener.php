<?php

namespace Modules\Bookings\app\Listeners;

use Modules\Bookings\app\Events\BookingCancelled;
use Modules\Bookings\app\Jobs\SendBookingCancelledJob;
use Modules\Bookings\app\Services\BookingMeetingSyncService;

class HandleBookingCancelledListener
{
    public function __construct(private readonly BookingMeetingSyncService $meetingSync) {}

    public function handle(BookingCancelled $event): void
    {
        $this->meetingSync->cancelBookingEvent($event->booking);

        SendBookingCancelledJob::dispatch($event->booking->id);
    }
}

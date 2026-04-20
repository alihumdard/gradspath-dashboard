<?php

namespace Modules\Bookings\app\Listeners;

use Modules\Bookings\app\Events\BookingCancelled;
use Modules\Bookings\app\Jobs\SendBookingCancelledJob;
use Modules\Bookings\app\Services\BookingCalendarSyncService;

class HandleBookingCancelledListener
{
    public function __construct(private readonly BookingCalendarSyncService $calendarSync) {}

    public function handle(BookingCancelled $event): void
    {
        $this->calendarSync->cancelBookingEvent($event->booking);

        SendBookingCancelledJob::dispatch($event->booking->id);
    }
}

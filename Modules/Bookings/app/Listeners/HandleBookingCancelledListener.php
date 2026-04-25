<?php

namespace Modules\Bookings\app\Listeners;

use Modules\Bookings\app\Events\BookingCancelled;
use Modules\Bookings\app\Jobs\SendBookingCancelledJob;
use Modules\Bookings\app\Services\BookingMeetingSyncService;
use Modules\Payments\app\Services\BookingRefundService;
use Modules\Payments\app\Services\MentorPayoutService;

class HandleBookingCancelledListener
{
    public function __construct(
        private readonly BookingMeetingSyncService $meetingSync,
        private readonly MentorPayoutService $payouts,
        private readonly BookingRefundService $refunds,
    ) {}

    public function handle(BookingCancelled $event): void
    {
        $this->meetingSync->cancelBookingEvent($event->booking);
        $this->payouts->reverseForCancelledBooking($event->booking);
        $this->refunds->refundCancelledBooking($event->booking);

        SendBookingCancelledJob::dispatch($event->booking->id);
    }
}

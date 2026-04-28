<?php

namespace Modules\Bookings\app\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Bookings\app\Events\BookingCreated;
use Modules\Bookings\app\Jobs\SendBookingConfirmationJob;

class GenerateMeetingLinkListener
{
    public function handle(BookingCreated $event): void
    {
        Log::info('Generate meeting link listener handling booking.', [
            'booking_id' => $event->booking->id,
            'status' => $event->booking->status,
            'meeting_type' => $event->booking->meeting_type,
            'session_type' => $event->booking->session_type,
        ]);

        Log::info('Dispatching booking confirmation after meeting sync.', [
            'booking_id' => $event->booking->id,
            'calendar_provider' => $event->booking->calendar_provider,
            'calendar_sync_status' => $event->booking->calendar_sync_status,
            'meeting_link_present' => filled($event->booking->meeting_link),
            'external_calendar_event_id' => $event->booking->external_calendar_event_id,
        ]);

        SendBookingConfirmationJob::dispatch($event->booking->id);
    }
}

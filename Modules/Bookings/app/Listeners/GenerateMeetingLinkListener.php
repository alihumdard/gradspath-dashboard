<?php

namespace Modules\Bookings\app\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Bookings\app\Events\BookingCreated;
use Modules\Bookings\app\Jobs\SendBookingConfirmationJob;
use Modules\Bookings\app\Services\BookingMeetingSyncService;

class GenerateMeetingLinkListener
{
    public function __construct(private readonly BookingMeetingSyncService $meetingSync) {}

    public function handle(BookingCreated $event): void
    {
        Log::info('Generate meeting link listener handling booking.', [
            'booking_id' => $event->booking->id,
            'status' => $event->booking->status,
            'meeting_type' => $event->booking->meeting_type,
            'session_type' => $event->booking->session_type,
        ]);

        try {
            $booking = $this->meetingSync->syncCreatedBooking($event->booking);
        } catch (\Throwable $exception) {
            Log::warning('Booking created but meeting sync failed unexpectedly.', [
                'booking_id' => $event->booking->id,
                'error' => $exception->getMessage(),
            ]);

            $booking = $event->booking->fresh(['booker', 'mentor.user', 'participantRecords', 'service']) ?? $event->booking;
        }

        Log::info('Dispatching booking confirmation after meeting sync.', [
            'booking_id' => $booking->id,
            'calendar_provider' => $booking->calendar_provider,
            'calendar_sync_status' => $booking->calendar_sync_status,
            'meeting_link_present' => filled($booking->meeting_link),
            'external_calendar_event_id' => $booking->external_calendar_event_id,
        ]);

        SendBookingConfirmationJob::dispatch($booking->id);
    }
}

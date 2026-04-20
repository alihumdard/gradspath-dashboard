<?php

namespace Modules\Bookings\app\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Bookings\app\Events\BookingCreated;
use Modules\Bookings\app\Jobs\SendBookingConfirmationJob;
use Modules\Bookings\app\Services\BookingCalendarSyncService;

class GenerateMeetingLinkListener
{
    public function __construct(private readonly BookingCalendarSyncService $calendarSync) {}

    public function handle(BookingCreated $event): void
    {
        try {
            $booking = $this->calendarSync->syncCreatedBooking($event->booking);
        } catch (\Throwable $exception) {
            Log::warning('Booking created but calendar sync failed unexpectedly.', [
                'booking_id' => $event->booking->id,
                'error' => $exception->getMessage(),
            ]);

            $booking = $event->booking->fresh(['booker', 'mentor.user', 'participantRecords', 'service']) ?? $event->booking;
        }

        SendBookingConfirmationJob::dispatch($booking->id);
    }
}

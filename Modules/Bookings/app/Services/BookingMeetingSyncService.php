<?php

namespace Modules\Bookings\app\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Bookings\app\Models\Booking;

class BookingMeetingSyncService
{
    public function __construct(private readonly ZoomService $zoom) {}

    public function syncCreatedBooking(Booking $booking): Booking
    {
        Log::info('Booking meeting sync requested.', [
            'booking_id' => $booking->id,
            'status' => $booking->status,
            'session_type' => $booking->session_type,
            'meeting_type' => $booking->meeting_type,
            'calendar_provider' => $booking->calendar_provider,
            'calendar_sync_status' => $booking->calendar_sync_status,
            'external_calendar_event_id' => $booking->external_calendar_event_id,
        ]);

        if (! $this->supportsCalendarSyncColumns()) {
            Log::warning('Booking meeting sync skipped because required bookings table columns are missing.', [
                'booking_id' => $booking->id,
                'missing_columns' => $this->missingCalendarSyncColumns(),
            ]);

            return $booking->fresh(['booker', 'mentor.user', 'participantRecords', 'service']);
        }

        if ($booking->session_type === 'office_hours') {
            Log::info('Booking meeting sync skipped for office hours booking.', [
                'booking_id' => $booking->id,
            ]);

            return $this->markSkipped($booking, 'Office hours are not synced to Zoom in this pass.');
        }

        if ($booking->status !== 'confirmed') {
            Log::info('Booking meeting sync skipped because booking is not confirmed.', [
                'booking_id' => $booking->id,
                'status' => $booking->status,
            ]);

            return $this->markSkipped($booking, 'Only confirmed bookings sync to Zoom.');
        }

        if (! $this->zoom->isConfigured()) {
            Log::warning('Booking meeting sync skipped because Zoom is not configured.', [
                'booking_id' => $booking->id,
            ]);

            return $this->markSkipped($booking, 'Zoom is not configured.');
        }

        try {
            $meeting = $this->zoom->createMeeting($booking);

            Log::info('Persisting Zoom meeting details to booking.', [
                'booking_id' => $booking->id,
                'meeting_id' => data_get($meeting, 'id'),
                'join_url_present' => filled(data_get($meeting, 'join_url')),
                'start_url_present' => filled(data_get($meeting, 'start_url')),
                'host_email_present' => filled(data_get($meeting, 'host_email')),
            ]);

            $booking->forceFill([
                'meeting_type' => 'zoom',
                'meeting_link' => data_get($meeting, 'join_url'),
                'external_calendar_event_id' => (string) data_get($meeting, 'id'),
                'calendar_provider' => 'zoom',
                'calendar_sync_status' => 'synced',
                'calendar_last_error' => null,
            ])->save();

            Log::info('Booking Zoom sync completed.', [
                'booking_id' => $booking->id,
                'meeting_id' => $booking->external_calendar_event_id,
                'calendar_sync_status' => $booking->calendar_sync_status,
                'meeting_link_present' => filled($booking->meeting_link),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Booking Zoom sync failed.', [
                'booking_id' => $booking->id,
                'exception_class' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            $booking->forceFill([
                'calendar_provider' => 'zoom',
                'calendar_sync_status' => 'failed',
                'calendar_last_error' => $exception->getMessage(),
            ])->save();
        }

        return $booking->fresh(['booker', 'mentor.user', 'participantRecords', 'service']);
    }

    public function cancelBookingEvent(Booking $booking): Booking
    {
        Log::info('Booking meeting cancellation sync requested.', [
            'booking_id' => $booking->id,
            'calendar_provider' => $booking->calendar_provider,
            'external_calendar_event_id' => $booking->external_calendar_event_id,
            'calendar_sync_status' => $booking->calendar_sync_status,
        ]);

        if (! $this->supportsCalendarSyncColumns()) {
            Log::warning('Booking meeting cancellation sync skipped because required bookings table columns are missing.', [
                'booking_id' => $booking->id,
                'missing_columns' => $this->missingCalendarSyncColumns(),
            ]);

            return $booking;
        }

        if ($booking->calendar_provider !== 'zoom' || ! $booking->external_calendar_event_id) {
            Log::info('Booking meeting cancellation sync skipped because booking is not a synced Zoom meeting.', [
                'booking_id' => $booking->id,
                'calendar_provider' => $booking->calendar_provider,
                'external_calendar_event_id' => $booking->external_calendar_event_id,
            ]);

            return $booking;
        }

        if (! $this->zoom->isConfigured()) {
            $booking->forceFill([
                'calendar_sync_status' => 'cancel_pending',
                'calendar_last_error' => 'Zoom is not configured for cancellation sync.',
            ])->save();

            return $booking;
        }

        try {
            $this->zoom->cancelMeeting((string) $booking->external_calendar_event_id);

            $booking->forceFill([
                'calendar_sync_status' => 'cancelled',
                'calendar_last_error' => null,
            ])->save();

            Log::info('Booking Zoom cancellation completed.', [
                'booking_id' => $booking->id,
                'meeting_id' => $booking->external_calendar_event_id,
                'calendar_sync_status' => $booking->calendar_sync_status,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Booking Zoom cancellation failed.', [
                'booking_id' => $booking->id,
                'meeting_id' => $booking->external_calendar_event_id,
                'exception_class' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            $booking->forceFill([
                'calendar_sync_status' => 'cancel_failed',
                'calendar_last_error' => $exception->getMessage(),
            ])->save();
        }

        return $booking->fresh();
    }

    private function markSkipped(Booking $booking, string $reason): Booking
    {
        Log::info('Booking meeting sync marked as skipped.', [
            'booking_id' => $booking->id,
            'reason' => $reason,
        ]);

        $booking->forceFill([
            'calendar_provider' => 'zoom',
            'calendar_sync_status' => 'skipped',
            'calendar_last_error' => $reason,
        ])->save();

        return $booking->fresh();
    }

    private function supportsCalendarSyncColumns(): bool
    {
        return $this->missingCalendarSyncColumns() === [];
    }

    private function missingCalendarSyncColumns(): array
    {
        $required = [
            'external_calendar_event_id',
            'calendar_provider',
            'calendar_sync_status',
            'calendar_last_error',
        ];

        return array_values(array_filter($required, fn (string $column) => ! Schema::hasColumn('bookings', $column)));
    }
}

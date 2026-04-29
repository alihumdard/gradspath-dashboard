<?php

namespace Modules\Bookings\app\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Bookings\app\Models\Booking;
use Modules\OfficeHours\app\Models\OfficeHourSession;

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
            return $this->syncOfficeHoursBooking($booking);
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

            return $this->markFailed($booking, 'Zoom booking is not configured.');
        }

        if (! $this->zoom->hasConnectedMentor($booking->mentor)) {
            Log::warning('Booking meeting sync skipped because mentor has not connected Zoom.', [
                'booking_id' => $booking->id,
                'mentor_id' => $booking->mentor_id,
            ]);

            return $this->markFailed($booking, 'This mentor must connect Zoom before students can book Zoom meetings.');
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

    private function syncOfficeHoursBooking(Booking $booking): Booking
    {
        if (! $this->supportsOfficeHourSessionSyncColumns()) {
            return $this->markFailed($booking, 'Office hours meeting sync columns are missing.');
        }

        $session = OfficeHourSession::query()
            ->lockForUpdate()
            ->find($booking->office_hour_session_id);

        if (! $session) {
            return $this->markFailed($booking, 'Office-hours session was not found for meeting sync.');
        }

        if (
            (string) $session->calendar_sync_status === 'synced'
            && filled($session->meeting_link)
            && filled($session->external_calendar_event_id)
        ) {
            return $this->copyOfficeHoursMeetingToBooking($booking, $session);
        }

        if ($booking->status !== 'confirmed') {
            return $this->markFailed($booking, 'Only confirmed office-hours bookings sync to Zoom.');
        }

        if (! $this->zoom->isConfigured()) {
            return $this->markOfficeHoursFailed($booking, $session, 'Zoom booking is not configured.');
        }

        if (! $this->zoom->hasConnectedMentor($booking->mentor)) {
            return $this->markOfficeHoursFailed($booking, $session, 'This mentor must connect Zoom before students can book Zoom meetings.');
        }

        try {
            $meeting = $this->zoom->createMeeting($booking);
            $joinUrl = (string) data_get($meeting, 'join_url', '');
            $meetingId = (string) data_get($meeting, 'id', '');

            if ($joinUrl === '' || $meetingId === '') {
                throw new \RuntimeException('Zoom did not return a meeting link.');
            }

            $session->forceFill([
                'meeting_link' => $joinUrl,
                'external_calendar_event_id' => $meetingId,
                'calendar_provider' => 'zoom',
                'calendar_sync_status' => 'synced',
                'calendar_last_error' => null,
            ])->save();

            return $this->copyOfficeHoursMeetingToBooking($booking, $session);
        } catch (\Throwable $exception) {
            Log::warning('Office hours Zoom sync failed.', [
                'booking_id' => $booking->id,
                'office_hour_session_id' => $session->id,
                'exception_class' => $exception::class,
                'error' => $exception->getMessage(),
            ]);

            return $this->markOfficeHoursFailed($booking, $session, $exception->getMessage());
        }
    }

    private function copyOfficeHoursMeetingToBooking(Booking $booking, OfficeHourSession $session): Booking
    {
        $booking->forceFill([
            'meeting_type' => 'zoom',
            'meeting_link' => $session->meeting_link,
            'external_calendar_event_id' => $session->external_calendar_event_id,
            'calendar_provider' => $session->calendar_provider ?: 'zoom',
            'calendar_sync_status' => $session->calendar_sync_status ?: 'synced',
            'calendar_last_error' => $session->calendar_last_error,
        ])->save();

        return $booking->fresh(['booker', 'mentor.user', 'participantRecords', 'service', 'officeHourSession']);
    }

    private function markOfficeHoursFailed(Booking $booking, OfficeHourSession $session, string $reason): Booking
    {
        $session->forceFill([
            'calendar_provider' => 'zoom',
            'calendar_sync_status' => 'failed',
            'calendar_last_error' => $reason,
        ])->save();

        return $this->markFailed($booking, $reason);
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

        if ($booking->session_type === 'office_hours') {
            Log::info('Booking meeting cancellation sync skipped for office-hours attendee booking.', [
                'booking_id' => $booking->id,
                'office_hour_session_id' => $booking->office_hour_session_id,
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
                'calendar_last_error' => 'Zoom booking is not configured for cancellation sync.',
            ])->save();

            return $booking;
        }

        if (! $this->zoom->hasConnectedMentor($booking->mentor)) {
            $booking->forceFill([
                'calendar_sync_status' => 'cancel_pending',
                'calendar_last_error' => 'The mentor Zoom connection is no longer active for cancellation sync.',
            ])->save();

            return $booking;
        }

        try {
            $this->zoom->cancelMeeting($booking);

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

    private function markFailed(Booking $booking, string $reason): Booking
    {
        Log::warning('Booking meeting sync marked as failed.', [
            'booking_id' => $booking->id,
            'reason' => $reason,
        ]);

        $booking->forceFill([
            'calendar_provider' => 'zoom',
            'calendar_sync_status' => 'failed',
            'calendar_last_error' => $reason,
        ])->save();

        return $booking->fresh();
    }

    private function supportsCalendarSyncColumns(): bool
    {
        return $this->missingCalendarSyncColumns() === [];
    }

    private function supportsOfficeHourSessionSyncColumns(): bool
    {
        return Schema::hasTable('office_hour_sessions')
            && collect([
                'meeting_link',
                'external_calendar_event_id',
                'calendar_provider',
                'calendar_sync_status',
                'calendar_last_error',
            ])->every(fn (string $column) => Schema::hasColumn('office_hour_sessions', $column));
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

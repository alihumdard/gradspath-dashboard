<?php

namespace Modules\Bookings\app\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Bookings\app\Models\Booking;

class BookingCalendarSyncService
{
    public function __construct(private readonly GoogleCalendarService $googleCalendar) {}

    public function syncCreatedBooking(Booking $booking): Booking
    {
        if (!$this->supportsCalendarSyncColumns()) {
            Log::warning('Booking calendar sync skipped because required bookings table columns are missing.', [
                'booking_id' => $booking->id,
                'missing_columns' => $this->missingCalendarSyncColumns(),
            ]);

            return $booking->fresh(['booker', 'mentor.user', 'participantRecords', 'service']);
        }

        if ($booking->session_type === 'office_hours') {
            return $this->markSkipped($booking, 'Office hours are not synced to Google Calendar in this pass.');
        }

        if ($booking->status !== 'confirmed') {
            return $this->markSkipped($booking, 'Only confirmed bookings sync to Google Calendar.');
        }

        if (!$this->googleCalendar->isConfigured()) {
            return $this->markSkipped($booking, 'Google Calendar is not configured.');
        }

        try {
            $event = $this->googleCalendar->createEvent($this->eventPayload($booking));

            $meetingLink = data_get($event, 'hangoutLink')
                ?: data_get($event, 'conferenceData.entryPoints.0.uri')
                ?: data_get($event, 'htmlLink');

            $booking->forceFill([
                'meeting_type' => 'google_meet',
                'meeting_link' => $meetingLink,
                'external_calendar_event_id' => data_get($event, 'id'),
                'calendar_provider' => 'google_calendar',
                'calendar_sync_status' => 'synced',
                'calendar_last_error' => null,
            ])->save();
        } catch (\Throwable $exception) {
            Log::warning('Booking calendar sync failed.', [
                'booking_id' => $booking->id,
                'error' => $exception->getMessage(),
            ]);

            $booking->forceFill([
                'calendar_provider' => 'google_calendar',
                'calendar_sync_status' => 'failed',
                'calendar_last_error' => $exception->getMessage(),
            ])->save();
        }

        return $booking->fresh(['booker', 'mentor.user', 'participantRecords', 'service']);
    }

    public function cancelBookingEvent(Booking $booking): Booking
    {
        if (!$this->supportsCalendarSyncColumns()) {
            Log::warning('Booking calendar cancellation sync skipped because required bookings table columns are missing.', [
                'booking_id' => $booking->id,
                'missing_columns' => $this->missingCalendarSyncColumns(),
            ]);

            return $booking;
        }

        if ($booking->calendar_provider !== 'google_calendar' || !$booking->external_calendar_event_id) {
            return $booking;
        }

        if (!$this->googleCalendar->isConfigured()) {
            $booking->forceFill([
                'calendar_sync_status' => 'cancel_pending',
                'calendar_last_error' => 'Google Calendar is not configured for cancellation sync.',
            ])->save();

            return $booking;
        }

        try {
            $this->googleCalendar->cancelEvent($booking->external_calendar_event_id);

            $booking->forceFill([
                'calendar_sync_status' => 'cancelled',
                'calendar_last_error' => null,
            ])->save();
        } catch (\Throwable $exception) {
            Log::warning('Booking calendar cancellation failed.', [
                'booking_id' => $booking->id,
                'event_id' => $booking->external_calendar_event_id,
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
        $booking->forceFill([
            'calendar_provider' => 'google_calendar',
            'calendar_sync_status' => 'skipped',
            'calendar_last_error' => $reason,
        ])->save();

        return $booking->fresh();
    }

    private function eventPayload(Booking $booking): array
    {
        $start = $booking->sessionAtInTimezone() ?: now($booking->session_timezone ?: config('app.timezone', 'UTC'));
        $end = $start->copy()->addMinutes(max((int) $booking->duration_minutes, 1));
        $mentorName = $booking->mentor?->user?->name ?? 'Mentor';
        $bookerName = $booking->booker?->name ?? 'Booker';

        return [
            'summary' => sprintf('%s with %s and %s', $booking->service?->service_name ?? 'Grads Paths Session', $bookerName, $mentorName),
            'description' => $this->eventDescription($booking),
            'start' => $this->eventDateTimePayload($start, $booking->session_timezone),
            'end' => $this->eventDateTimePayload($end, $booking->session_timezone),
            'attendees' => $this->attendees($booking),
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => 'booking-'.$booking->id.'-'.($booking->updated_at?->timestamp ?? time()),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ];
    }

    private function attendees(Booking $booking): array
    {
        $attendees = [];

        $mentorEmail = trim((string) $booking->mentor?->user?->email);
        if ($mentorEmail !== '') {
            $attendees[] = [
                'email' => $mentorEmail,
                'displayName' => $booking->mentor?->user?->name ?? 'Mentor',
            ];
        }

        foreach ($booking->participantRecords as $participant) {
            $email = trim((string) $participant->email);

            if ($email === '') {
                continue;
            }

            $attendees[] = [
                'email' => $email,
                'displayName' => $participant->full_name ?: 'Participant',
            ];
        }

        return collect($attendees)
            ->unique('email')
            ->values()
            ->all();
    }

    private function eventDescription(Booking $booking): string
    {
        $lines = [
            'Grads Paths booking confirmed.',
            'Service: '.($booking->service?->service_name ?? 'Service'),
            'Session type: '.$this->sessionTypeLabel($booking->session_type),
            'Timezone: '.($booking->session_timezone ?: config('app.timezone', 'UTC')),
            'Booking ID: '.$booking->id,
        ];

        if ($booking->booker?->email) {
            $lines[] = 'Booker email: '.$booking->booker->email;
        }

        return implode("\n", $lines);
    }

    private function eventDateTimePayload(Carbon $dateTime, ?string $timezone): array
    {
        return [
            'dateTime' => $dateTime->toIso8601String(),
            'timeZone' => $timezone ?: config('app.timezone', 'UTC'),
        ];
    }

    private function sessionTypeLabel(?string $sessionType): string
    {
        return match ($sessionType) {
            '1on3' => '1 on 3',
            '1on5' => '1 on 5',
            default => '1 on 1',
        };
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

        return array_values(array_filter($required, fn (string $column) => !Schema::hasColumn('bookings', $column)));
    }
}

<?php

namespace Modules\Bookings\app\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\BookingMeetingEvent;

class ZoomWebhookService
{
    public function __construct(
        private readonly ZoomService $zoom,
        private readonly BookingAttendanceResolver $attendance,
    ) {}

    public function verifyRequest(string $payload, string $signature, string $timestamp): void
    {
        $this->zoom->verifyWebhookSignature($payload, $signature, $timestamp);
    }

    public function validationResponse(array $payload): array
    {
        $plainToken = (string) data_get($payload, 'payload.plainToken', '');

        if ($plainToken === '') {
            throw new \RuntimeException('Missing Zoom plainToken.');
        }

        return [
            'plainToken' => $plainToken,
            'encryptedToken' => $this->zoom->webhookValidationToken($plainToken),
        ];
    }

    public function process(array $payload): BookingMeetingEvent
    {
        $eventType = (string) ($payload['event'] ?? 'unknown');
        $eventId = (string) ($payload['event_id'] ?? '');
        $meetingId = $this->normalizeMeetingId(
            data_get($payload, 'payload.object.id')
                ?? data_get($payload, 'payload.object.uuid')
                ?? data_get($payload, 'payload.object.meeting_id')
        );
        $payloadHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: serialize($payload));

        Log::info('Processing Zoom webhook event.', [
            'event_type' => $eventType,
            'event_id' => $eventId !== '' ? $eventId : null,
            'meeting_id' => $meetingId,
        ]);

        return DB::transaction(function () use ($payload, $eventType, $eventId, $meetingId, $payloadHash) {
            $existing = BookingMeetingEvent::query()->where('payload_hash', $payloadHash)->lockForUpdate()->first();

            if ($existing) {
                Log::info('Skipping duplicate Zoom webhook event.', [
                    'event_type' => $eventType,
                    'event_id' => $eventId !== '' ? $eventId : null,
                    'meeting_id' => $meetingId,
                    'booking_meeting_event_id' => $existing->id,
                ]);

                return $existing;
            }

            $booking = $meetingId === null
                ? null
                : Booking::query()->where('external_calendar_event_id', $meetingId)->first();

            Log::info('Resolved Zoom webhook booking match.', [
                'event_type' => $eventType,
                'meeting_id' => $meetingId,
                'booking_id' => $booking?->id,
            ]);

            $event = BookingMeetingEvent::create([
                'booking_id' => $booking?->id,
                'provider' => 'zoom',
                'provider_meeting_id' => $meetingId,
                'event_id' => $eventId !== '' ? $eventId : null,
                'event_type' => $eventType,
                'occurred_at' => $this->occurredAt($payload, $eventType),
                'received_at' => now(),
                'meeting_started_at' => $this->meetingStartedAt($payload, $eventType),
                'meeting_ended_at' => $this->meetingEndedAt($payload, $eventType),
                'host_joined_at' => $this->hostJoinedAt($payload, $eventType, $booking, $meetingId),
                'first_participant_joined_at' => $this->participantJoinedAt($payload, $eventType),
                'is_verified' => true,
                'processed' => true,
                'payload_hash' => $payloadHash,
                'payload' => $payload,
            ]);

            Log::info('Stored Zoom webhook event.', [
                'booking_meeting_event_id' => $event->id,
                'booking_id' => $booking?->id,
                'event_type' => $eventType,
                'meeting_id' => $meetingId,
                'occurred_at' => optional($event->occurred_at)?->toIso8601String(),
            ]);

            if ($booking) {
                $booking = $this->attendance->refresh($booking);

                Log::info('Refreshed booking attendance from Zoom webhook.', [
                    'booking_id' => $booking->id,
                    'event_type' => $eventType,
                    'attendance_status' => $booking->attendance_status,
                    'actual_started_at' => optional($booking->actual_started_at)?->toIso8601String(),
                    'actual_ended_at' => optional($booking->actual_ended_at)?->toIso8601String(),
                    'host_joined_at' => optional($booking->host_joined_at)?->toIso8601String(),
                    'first_attendee_joined_at' => optional($booking->first_attendee_joined_at)?->toIso8601String(),
                    'attendance_overlap_minutes' => $booking->attendance_overlap_minutes,
                ]);
            } else {
                Log::warning('Zoom webhook event did not match a booking.', [
                    'event_type' => $eventType,
                    'meeting_id' => $meetingId,
                ]);
            }

            return $event;
        });
    }

    private function occurredAt(array $payload, ?string $eventType = null): ?Carbon
    {
        $value = match ($eventType) {
            'meeting.participant_joined' => data_get($payload, 'payload.object.participant.join_time')
                ?? data_get($payload, 'payload.object.start_time')
                ?? data_get($payload, 'event_ts'),
            'meeting.participant_left' => data_get($payload, 'payload.object.participant.leave_time')
                ?? data_get($payload, 'payload.object.end_time')
                ?? data_get($payload, 'event_ts'),
            'meeting.ended' => data_get($payload, 'payload.object.end_time')
                ?? data_get($payload, 'event_ts'),
            default => data_get($payload, 'payload.object.start_time')
                ?? data_get($payload, 'payload.object.end_time')
                ?? data_get($payload, 'event_ts'),
        };

        if (is_numeric($value)) {
            return Carbon::createFromTimestampMs((int) $value);
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return null;
    }

    private function meetingStartedAt(array $payload, string $eventType): ?Carbon
    {
        if ($eventType !== 'meeting.started') {
            return null;
        }

        return $this->occurredAt($payload, $eventType);
    }

    private function meetingEndedAt(array $payload, string $eventType): ?Carbon
    {
        if ($eventType !== 'meeting.ended') {
            return null;
        }

        return $this->occurredAt($payload, $eventType);
    }

    private function hostJoinedAt(array $payload, string $eventType, ?Booking $booking, ?string $meetingId): ?Carbon
    {
        if ($eventType !== 'meeting.participant_joined') {
            return null;
        }

        $participantIdentity = $this->normalizeIdentity(
            data_get($payload, 'payload.object.participant.email')
                ?? data_get($payload, 'payload.object.participant.user_email')
                ?? data_get($payload, 'payload.object.participant.user_name')
        );

        $hostIdentity = $this->hostIdentity($payload, $booking, $meetingId);

        $isHost = (bool) data_get($payload, 'payload.object.participant.host', false)
            || ($participantIdentity !== null && $hostIdentity !== null && $participantIdentity === $hostIdentity);

        return $isHost ? $this->occurredAt($payload, $eventType) : null;
    }

    private function participantJoinedAt(array $payload, string $eventType): ?Carbon
    {
        if ($eventType !== 'meeting.participant_joined') {
            return null;
        }

        return $this->occurredAt($payload, $eventType);
    }

    private function normalizeMeetingId(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function hostIdentity(array $payload, ?Booking $booking, ?string $meetingId): ?string
    {
        $hostIdentity = $this->normalizeIdentity(data_get($payload, 'payload.object.host_email'));

        if ($hostIdentity !== null) {
            return $hostIdentity;
        }

        $query = BookingMeetingEvent::query()->where('provider', 'zoom');

        if ($booking) {
            $query->where('booking_id', $booking->id);
        } elseif ($meetingId !== null) {
            $query->where('provider_meeting_id', $meetingId);
        } else {
            return null;
        }

        $recentHostIdentity = $query
            ->orderByDesc('id')
            ->get()
            ->map(fn (BookingMeetingEvent $event) => $this->normalizeIdentity(data_get($event->payload, 'payload.object.host_email')))
            ->first(fn (?string $value) => $value !== null);

        return $recentHostIdentity;
    }

    private function normalizeIdentity(mixed $value): ?string
    {
        $normalized = mb_strtolower(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }
}

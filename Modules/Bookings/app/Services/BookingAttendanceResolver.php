<?php

namespace Modules\Bookings\app\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Services\MentorPayoutService;

class BookingAttendanceResolver
{
    public function __construct(private readonly MentorPayoutService $payouts) {}

    public function refresh(Booking $booking): Booking
    {
        $booking->loadMissing('meetingEvents');

        $startedAt = $booking->meetingEvents
            ->pluck('meeting_started_at')
            ->filter()
            ->sort()
            ->first();

        $endedAt = $booking->meetingEvents
            ->pluck('meeting_ended_at')
            ->filter()
            ->sort()
            ->last();

        $hostJoinedAt = $booking->meetingEvents
            ->pluck('host_joined_at')
            ->filter()
            ->sort()
            ->first();

        $firstAttendeeJoinedAt = $booking->meetingEvents
            ->filter(fn ($event) => $event->event_type === 'meeting.participant_joined' && $event->host_joined_at === null)
            ->pluck('first_participant_joined_at')
            ->filter()
            ->sort()
            ->first();

        $lastParticipantLeftAt = $booking->meetingEvents
            ->filter(fn ($event) => $event->event_type === 'meeting.participant_left')
            ->pluck('occurred_at')
            ->filter()
            ->sort()
            ->last();

        $actualStartedAt = $startedAt
            ?? $this->earliest($hostJoinedAt, $firstAttendeeJoinedAt);
        $actualEndedAt = $endedAt
            ?? $lastParticipantLeftAt;

        $overlapMinutes = null;

        if ($hostJoinedAt && $firstAttendeeJoinedAt && $actualEndedAt) {
            $overlapStart = $hostJoinedAt->greaterThan($firstAttendeeJoinedAt)
                ? $hostJoinedAt
                : $firstAttendeeJoinedAt;

            if ($actualEndedAt->greaterThan($overlapStart)) {
                $overlapMinutes = max($overlapStart->diffInMinutes($actualEndedAt), 0);
            } else {
                $overlapMinutes = 0;
            }
        }

        $attendanceStatus = $this->attendanceStatus(
            $booking,
            $hostJoinedAt,
            $firstAttendeeJoinedAt,
            $actualEndedAt,
            $overlapMinutes,
        );

        $feedbackUnlockedAt = $this->feedbackUnlockedAt(
            $booking,
            $attendanceStatus,
            $actualEndedAt,
        );

        $updates = [
            'attendance_status' => $attendanceStatus,
            'actual_started_at' => $actualStartedAt,
            'actual_ended_at' => $actualEndedAt,
            'host_joined_at' => $hostJoinedAt,
            'first_attendee_joined_at' => $firstAttendeeJoinedAt,
            'attendance_overlap_minutes' => $overlapMinutes,
            'feedback_unlocked_at' => $feedbackUnlockedAt,
        ];

        if ($booking->completion_source !== 'manual') {
            if ($attendanceStatus === 'attended') {
                $updates['session_outcome'] = 'completed';
            } elseif (in_array($attendanceStatus, ['no_show_mentor', 'no_show_student', 'interrupted', 'unknown'], true)) {
                $updates['session_outcome'] = $attendanceStatus;
            }
        }

        if ($attendanceStatus === 'attended' && $actualEndedAt) {
            if ($booking->status === 'confirmed') {
                $updates['status'] = 'completed';
            }

            if ($booking->completed_at === null) {
                $updates['completed_at'] = $actualEndedAt;
                $updates['completion_source'] = 'zoom_event';
            }
        }

        $booking->forceFill($updates)->save();

        if ($booking->status === 'completed') {
            $this->payouts->releaseForCompletedBooking($booking);
        }

        Log::info('Booking attendance resolved.', [
            'booking_id' => $booking->id,
            'attendance_status' => $attendanceStatus,
            'classification_deadline' => $this->classificationDeadline($booking)->toIso8601String(),
            'actual_started_at' => optional($actualStartedAt)?->toIso8601String(),
            'actual_ended_at' => optional($actualEndedAt)?->toIso8601String(),
            'host_joined_at' => optional($hostJoinedAt)?->toIso8601String(),
            'first_attendee_joined_at' => optional($firstAttendeeJoinedAt)?->toIso8601String(),
            'attendance_overlap_minutes' => $overlapMinutes,
            'feedback_unlocked_at' => optional($feedbackUnlockedAt)?->toIso8601String(),
        ]);

        return $booking->fresh();
    }

    private function attendanceStatus(
        Booking $booking,
        ?Carbon $hostJoinedAt,
        ?Carbon $firstAttendeeJoinedAt,
        ?Carbon $actualEndedAt,
        ?int $overlapMinutes,
    ): string {
        if ($hostJoinedAt && $firstAttendeeJoinedAt && $actualEndedAt) {
            return ($overlapMinutes ?? 0) >= $this->minimumOverlapMinutes()
                ? 'attended'
                : 'interrupted';
        }

        if ($this->classificationDeadline($booking)->isFuture()) {
            return 'pending';
        }

        if ($hostJoinedAt && ! $firstAttendeeJoinedAt) {
            return 'no_show_student';
        }

        if ($firstAttendeeJoinedAt && ! $hostJoinedAt) {
            return 'no_show_mentor';
        }

        if ($hostJoinedAt || $firstAttendeeJoinedAt || $actualEndedAt) {
            return 'unknown';
        }

        return 'unknown';
    }

    private function feedbackUnlockedAt(Booking $booking, string $attendanceStatus, ?Carbon $actualEndedAt): ?Carbon
    {
        if ($attendanceStatus === 'attended' && $actualEndedAt) {
            return $actualEndedAt;
        }

        return $this->fallbackFeedbackUnlockAt($booking);
    }

    private function classificationDeadline(Booking $booking): Carbon
    {
        $start = $booking->session_at?->copy() ?? now()->utc();

        return $start->addMinutes($this->noShowGraceMinutes());
    }

    private function fallbackFeedbackUnlockAt(Booking $booking): ?Carbon
    {
        $scheduledEnd = $booking->session_at?->copy()->addMinutes(max((int) $booking->duration_minutes, 1));

        return $scheduledEnd?->addMinutes($this->feedbackFallbackGraceMinutes());
    }

    private function earliest(?Carbon $left, ?Carbon $right): ?Carbon
    {
        if (! $left) {
            return $right;
        }

        if (! $right) {
            return $left;
        }

        return $left->lessThanOrEqualTo($right) ? $left : $right;
    }

    private function minimumOverlapMinutes(): int
    {
        return (int) config('services.zoom.attendance.minimum_overlap_minutes', 5);
    }

    private function noShowGraceMinutes(): int
    {
        return (int) config('services.zoom.attendance.no_show_grace_minutes', 15);
    }

    private function feedbackFallbackGraceMinutes(): int
    {
        return (int) config('services.zoom.attendance.feedback_fallback_grace_minutes', 60);
    }
}

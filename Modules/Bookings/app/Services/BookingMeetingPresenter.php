<?php

namespace Modules\Bookings\app\Services;

use Modules\Bookings\app\Models\Booking;

class BookingMeetingPresenter
{
    public function attendanceStatus(Booking $booking): string
    {
        return $booking->attendance_status ?: 'pending';
    }

    public function attendanceLabel(Booking $booking): string
    {
        return match ($this->attendanceStatus($booking)) {
            'attended' => 'Attendance verified',
            'no_show_mentor' => 'Mentor no-show',
            'no_show_student' => 'Student no-show',
            'interrupted' => 'Interrupted',
            'unknown' => 'Attendance unclear',
            default => 'Awaiting attendance data',
        };
    }

    public function feedbackAllowed(Booking $booking): bool
    {
        return $booking->feedbackUnlocked();
    }

    public function feedbackUnlockReason(Booking $booking): string
    {
        if ($booking->attendance_status === 'attended') {
            return 'Feedback is available because Zoom attendance was verified.';
        }

        if ($booking->feedback_unlocked_at && $booking->feedback_unlocked_at->lessThanOrEqualTo(now())) {
            return 'Feedback is available because the scheduled fallback window has passed.';
        }

        if ($booking->feedback_unlocked_at) {
            return 'Feedback will unlock automatically if Zoom attendance stays incomplete.';
        }

        return 'Feedback will unlock after attendance is verified or the fallback window passes.';
    }

    public function providerLabel(Booking $booking): string
    {
        if ($this->isZoom($booking)) {
            return 'Zoom';
        }

        return 'Meeting Link';
    }

    public function linkLabel(Booking $booking): string
    {
        if ($this->isZoom($booking)) {
            return $this->scheduledState($booking) === 'ended'
                ? 'Open Zoom Meeting'
                : 'Join Zoom Meeting';
        }

        return 'Open Meeting Link';
    }

    public function statusMessage(Booking $booking): string
    {
        if ($booking->meeting_link) {
            return match ($this->scheduledState($booking)) {
                'live' => 'Meeting is live now. You can join with the Zoom link above.',
                'ended' => 'This meeting window has ended. The Zoom link is kept here for reference.',
                default => 'Zoom meeting link is ready.',
            };
        }

        return match ((string) $booking->calendar_sync_status) {
            'synced' => 'Zoom meeting is ready.',
            'failed' => 'Zoom meeting could not be generated automatically yet.',
            'skipped' => 'Zoom meeting is not generated for this booking type.',
            'cancelled' => 'Zoom meeting has been cancelled.',
            'cancel_failed' => 'Booking was cancelled, but Zoom meeting cancellation still needs attention.',
            'cancel_pending' => 'Booking was cancelled. Zoom cancellation will be retried after configuration is restored.',
            default => 'Zoom meeting link will be shared soon.',
        };
    }

    public function scheduledState(Booking $booking): string
    {
        if (! $booking->session_at) {
            return 'upcoming';
        }

        $start = $booking->session_at->copy();
        $end = $start->copy()->addMinutes(max((int) $booking->duration_minutes, 1));
        $now = now()->utc();

        if ($now->lt($start)) {
            return 'upcoming';
        }

        if ($now->lt($end)) {
            return 'live';
        }

        return 'ended';
    }

    public function scheduledStateLabel(Booking $booking): string
    {
        return match ($this->scheduledState($booking)) {
            'live' => 'Live / Join now',
            'ended' => 'Ended',
            default => 'Upcoming',
        };
    }

    private function isZoom(Booking $booking): bool
    {
        return $booking->meeting_type === 'zoom'
            || $booking->calendar_provider === 'zoom'
            || str_contains((string) $booking->meeting_link, 'zoom.us/');
    }
}

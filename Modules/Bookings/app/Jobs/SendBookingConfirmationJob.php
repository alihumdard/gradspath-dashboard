<?php

namespace Modules\Bookings\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Mail\MentorBookingNotificationMail;
use Modules\Bookings\app\Mail\StudentBookingConfirmationMail;

class SendBookingConfirmationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $bookingId) {}

    public function handle(): void
    {
        $booking = Booking::query()
            ->with(['booker', 'mentor.user', 'service', 'participantRecords'])
            ->find($this->bookingId);

        if (!$booking) {
            return;
        }

        if ($booking->session_type === 'office_hours') {
            return;
        }

        $mentorUser = $booking->mentor?->user;
        $mentorEmail = $this->normalizeEmail($mentorUser?->email);

        if ($mentorEmail) {
            Mail::to($mentorEmail)->send(new MentorBookingNotificationMail(
                $this->bookingDetails($booking, 'mentor'),
                $mentorUser?->name ?: 'Mentor',
            ));
        }

        $bookingRecipients = collect($booking->participantRecords)
            ->map(function ($participant) {
                return [
                    'email' => $this->normalizeEmail($participant->email),
                    'name' => $participant->full_name ?: 'Booker',
                ];
            })
            ->filter(fn (array $recipient) => $recipient['email'] !== null)
            ->unique('email')
            ->values();

        foreach ($bookingRecipients as $recipient) {
            Mail::to($recipient['email'])->send(new StudentBookingConfirmationMail(
                $this->bookingDetails($booking, 'student'),
                $recipient['name'],
            ));
        }
    }

    private function bookingDetails(Booking $booking, string $recipientType): array
    {
        $bookerName = $booking->booker?->name ?? 'Booker';
        $bookerEmail = $this->normalizeEmail($booking->booker?->email);
        $bookerLabel = $booking->booker?->hasRole('mentor') ? 'Mentor' : 'Student';
        $mentorName = $booking->mentor?->user?->name ?? 'Mentor';
        $sessionAt = $booking->sessionAtInTimezone();

        return [
            'booking_id' => $booking->id,
            'service_name' => $booking->service?->service_name ?? 'Service',
            'session_type_label' => $this->sessionTypeLabel($booking->session_type),
            'session_date' => $sessionAt?->format('l, F j, Y') ?? 'TBD',
            'session_time' => $sessionAt?->format('g:i A') ?? 'TBD',
            'session_timezone' => $booking->session_timezone,
            'meeting_link' => $booking->meeting_link,
            'meeting_provider' => $this->meetingProviderLabel($booking),
            'meeting_link_label' => $this->meetingLinkLabel($booking),
            'calendar_sync_status' => $booking->calendar_sync_status,
            'counterpart_name' => $recipientType === 'mentor' ? $bookerName : $mentorName,
            'counterpart_email' => $recipientType === 'mentor' ? $bookerEmail : null,
            'student_name' => $bookerName,
            'student_email' => $bookerEmail,
            'booker_name' => $bookerName,
            'booker_email' => $bookerEmail,
            'booker_label' => $bookerLabel,
            'mentor_name' => $mentorName,
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

    private function normalizeEmail(?string $email): ?string
    {
        $normalized = strtolower(trim((string) $email));

        return $normalized === '' ? null : $normalized;
    }

    private function meetingProviderLabel(Booking $booking): string
    {
        if ($this->isGoogleMeetLink($booking)) {
            return 'Google Calendar / Google Meet';
        }

        if ($booking->calendar_provider === 'google_calendar') {
            return 'Google Calendar Event';
        }

        return 'Meeting Link';
    }

    private function meetingLinkLabel(Booking $booking): string
    {
        if ($this->isGoogleMeetLink($booking)) {
            return 'Join Google Meet';
        }

        if ($booking->calendar_provider === 'google_calendar') {
            return 'Open Google Calendar Event';
        }

        return 'Open Meeting Link';
    }

    private function isGoogleMeetLink(Booking $booking): bool
    {
        return $booking->meeting_type === 'google_meet'
            || str_contains((string) $booking->meeting_link, 'meet.google.com');
    }
}

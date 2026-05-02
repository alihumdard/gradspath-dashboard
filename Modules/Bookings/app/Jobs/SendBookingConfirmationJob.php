<?php

namespace Modules\Bookings\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Modules\Bookings\app\Mail\MentorBookingNotificationMail;
use Modules\Bookings\app\Mail\StudentBookingConfirmationMail;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingMeetingPresenter;

class SendBookingConfirmationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $bookingId) {}

    public function handle(BookingMeetingPresenter $meetingPresenter): void
    {
        $booking = Booking::query()
            ->with(['booker', 'mentor.user', 'service', 'participantRecords', 'officeHourSession'])
            ->find($this->bookingId);

        if (! $booking) {
            return;
        }

        $mentorUser = $booking->mentor?->user;
        $mentorEmail = $this->normalizeEmail($mentorUser?->email);

        if ($mentorEmail) {
            Mail::to($mentorEmail)->send(new MentorBookingNotificationMail(
                $this->bookingDetails($booking, 'mentor', $meetingPresenter),
                $mentorUser?->name ?: 'Mentor',
            ));
        }

        $bookingRecipients = collect($booking->participantRecords)
            ->map(function ($participant) {
                return [
                    'email' => $this->normalizeEmail($participant->email),
                    'name' => $participant->full_name ?: 'Booker',
                    'user_id' => $participant->user_id,
                    'is_primary' => (bool) $participant->is_primary,
                ];
            })
            ->filter(fn (array $recipient) => $recipient['email'] !== null)
            ->unique('email')
            ->values();

        foreach ($bookingRecipients as $recipient) {
            Mail::to($recipient['email'])->send(new StudentBookingConfirmationMail(
                $this->bookingDetails($booking, 'student', $meetingPresenter, $recipient),
                $recipient['name'],
            ));
        }
    }

    private function bookingDetails(Booking $booking, string $recipientType, BookingMeetingPresenter $meetingPresenter, ?array $recipient = null): array
    {
        $bookerName = $booking->booker?->name ?? 'Booker';
        $bookerEmail = $this->normalizeEmail($booking->booker?->email);
        $bookerLabel = $booking->booker?->hasRole('mentor') ? 'Mentor' : 'Student';
        $mentorName = $booking->mentor?->user?->name ?? 'Mentor';
        $sessionAt = $booking->sessionAtInTimezone();

        $details = [
            'booking_id' => $booking->id,
            'service_name' => $booking->service?->service_name ?? 'Service',
            'session_type_label' => $this->sessionTypeLabel($booking->session_type),
            'session_date' => $sessionAt?->format('l, F j, Y') ?? 'TBD',
            'session_time' => $sessionAt?->format('g:i A') ?? 'TBD',
            'session_timezone' => $booking->session_timezone,
            'meeting_link' => $booking->meeting_link,
            'meeting_provider' => $meetingPresenter->providerLabel($booking),
            'meeting_link_label' => $meetingPresenter->linkLabel($booking),
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

        if ($recipientType === 'student' && $this->isSyncedZoomBooking($booking)) {
            if (($recipient['user_id'] ?? null) && ! ($recipient['is_primary'] ?? false)) {
                $details['meeting_link'] = route('student.bookings.join-meeting', $booking->id);
                $details['meeting_link_label'] = 'Join Zoom Meeting';
            } else {
                $details['meeting_link'] = $booking->meeting_link;
                $details['meeting_link_label'] = 'Open Zoom Meeting';
            }
        }

        if ($recipientType === 'mentor' && $this->isSyncedZoomBooking($booking)) {
            $details['meeting_link'] = route('mentor.bookings.start-meeting', $booking->id);
            $details['meeting_link_label'] = 'Start Zoom Meeting';
        }

        return $details;
    }

    private function sessionTypeLabel(?string $sessionType): string
    {
        return match ($sessionType) {
            'office_hours' => 'Office Hours',
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

    private function isSyncedZoomBooking(Booking $booking): bool
    {
        return $booking->calendar_provider === 'zoom'
            && $booking->calendar_sync_status === 'synced'
            && filled($booking->external_calendar_event_id);
    }
}

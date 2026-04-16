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
            ->with(['student', 'mentor.user', 'service', 'participantRecords'])
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

        $studentRecipients = collect($booking->participantRecords)
            ->map(function ($participant) {
                return [
                    'email' => $this->normalizeEmail($participant->email),
                    'name' => $participant->full_name ?: 'Student',
                ];
            })
            ->filter(fn (array $recipient) => $recipient['email'] !== null)
            ->unique('email')
            ->values();

        foreach ($studentRecipients as $recipient) {
            Mail::to($recipient['email'])->send(new StudentBookingConfirmationMail(
                $this->bookingDetails($booking, 'student'),
                $recipient['name'],
            ));
        }
    }

    private function bookingDetails(Booking $booking, string $recipientType): array
    {
        $studentName = $booking->student?->name ?? 'Student';
        $studentEmail = $this->normalizeEmail($booking->student?->email);
        $mentorName = $booking->mentor?->user?->name ?? 'Mentor';
        return [
            'booking_id' => $booking->id,
            'service_name' => $booking->service?->service_name ?? 'Service',
            'session_type_label' => $this->sessionTypeLabel($booking->session_type),
            'session_date' => $booking->session_at?->format('l, F j, Y') ?? 'TBD',
            'session_time' => $booking->session_at?->format('g:i A') ?? 'TBD',
            'session_timezone' => $booking->session_timezone,
            'meeting_link' => $booking->meeting_link,
            'counterpart_name' => $recipientType === 'mentor' ? $studentName : $mentorName,
            'counterpart_email' => $recipientType === 'mentor' ? $studentEmail : null,
            'student_name' => $studentName,
            'student_email' => $studentEmail,
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
}

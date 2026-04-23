<?php

namespace Modules\Bookings\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Modules\Bookings\app\Mail\BookingReminderMail;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingMeetingPresenter;

class SendBookingReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $bookingId,
        public int $hoursUntilSession,
    ) {}

    public function handle(BookingMeetingPresenter $meetingPresenter): void
    {
        $booking = Booking::query()
            ->with(['booker', 'mentor.user', 'service', 'participantRecords'])
            ->find($this->bookingId);

        if (! $booking || $booking->session_type === 'office_hours' || $booking->status !== 'confirmed') {
            return;
        }

        $details = [
            'booking_id' => $booking->id,
            'service_name' => $booking->service?->service_name ?? 'Service',
            'session_type_label' => match ($booking->session_type) {
                '1on3' => '1 on 3',
                '1on5' => '1 on 5',
                default => '1 on 1',
            },
            'session_date' => $booking->sessionAtInTimezone()?->format('l, F j, Y') ?? 'TBD',
            'session_time' => $booking->sessionAtInTimezone()?->format('g:i A') ?? 'TBD',
            'session_timezone' => $booking->session_timezone,
            'meeting_link' => $booking->meeting_link,
            'meeting_provider' => $meetingPresenter->providerLabel($booking),
            'meeting_link_label' => $meetingPresenter->linkLabel($booking),
            'mentor_name' => $booking->mentor?->user?->name ?? 'Mentor',
            'booker_name' => $booking->booker?->name ?? 'Booker',
            'hours_until_session' => $this->hoursUntilSession,
        ];

        $recipients = collect([
            [
                'email' => $booking->mentor?->user?->email,
                'name' => $booking->mentor?->user?->name ?: 'Mentor',
                'role' => 'mentor',
            ],
        ])->merge(
            $booking->participantRecords->map(fn ($participant) => [
                'email' => $participant->email,
                'name' => $participant->full_name ?: 'Participant',
                'role' => 'participant',
            ])
        )
            ->map(fn (array $recipient) => [
                'email' => $this->normalizeEmail($recipient['email'] ?? null),
                'name' => $recipient['name'] ?? 'Participant',
                'role' => $recipient['role'] ?? 'participant',
            ])
            ->filter(fn (array $recipient) => $recipient['email'] !== null)
            ->unique('email')
            ->values();

        foreach ($recipients as $recipient) {
            Mail::to($recipient['email'])->send(new BookingReminderMail(
                $details,
                $recipient['name'],
                $recipient['role'],
                $this->hoursUntilSession,
            ));
        }
    }

    private function normalizeEmail(?string $email): ?string
    {
        $normalized = strtolower(trim((string) $email));

        return $normalized === '' ? null : $normalized;
    }
}

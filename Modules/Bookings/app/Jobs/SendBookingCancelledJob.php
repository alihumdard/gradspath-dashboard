<?php

namespace Modules\Bookings\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Modules\Bookings\app\Mail\BookingCancelledNotificationMail;
use Modules\Bookings\app\Models\Booking;

class SendBookingCancelledJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $bookingId) {}

    public function handle(): void
    {
        $booking = Booking::query()
            ->with(['booker', 'mentor.user', 'service', 'participantRecords', 'cancelledBy'])
            ->find($this->bookingId);

        if (!$booking) {
            return;
        }

        $cancelledByName = $booking->cancelledBy?->name ?: 'A participant';
        $sessionAt = $booking->sessionAtInTimezone();

        $recipients = collect([
            [
                'email' => $booking->mentor?->user?->email,
                'name' => $booking->mentor?->user?->name ?: 'Mentor',
            ],
            [
                'email' => $booking->booker?->email,
                'name' => $booking->booker?->name ?: 'Booker',
            ],
        ])->merge(
            $booking->participantRecords->map(fn ($participant) => [
                'email' => $participant->email,
                'name' => $participant->full_name ?: 'Participant',
            ])
        )
            ->map(fn (array $recipient) => [
                'email' => $this->normalizeEmail($recipient['email'] ?? null),
                'name' => $recipient['name'] ?? 'Participant',
            ])
            ->filter(fn (array $recipient) => $recipient['email'] !== null)
            ->unique('email')
            ->values();

        foreach ($recipients as $recipient) {
            Mail::to($recipient['email'])->send(new BookingCancelledNotificationMail(
                [
                    'booking_id' => $booking->id,
                    'service_name' => $booking->service?->service_name ?? 'Service',
                    'session_date' => $sessionAt?->format('l, F j, Y') ?? 'TBD',
                    'session_time' => $sessionAt?->format('g:i A') ?? 'TBD',
                    'session_timezone' => $booking->session_timezone,
                    'mentor_name' => $booking->mentor?->user?->name ?? 'Mentor',
                    'cancelled_by_name' => $cancelledByName,
                    'support_message' => 'If you need help rebooking, please return to your bookings page or contact support.',
                ],
                $recipient['name'],
            ));
        }
    }

    private function normalizeEmail(?string $email): ?string
    {
        $normalized = strtolower(trim((string) $email));

        return $normalized === '' ? null : $normalized;
    }
}

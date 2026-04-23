<?php

namespace Modules\Bookings\app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingReminderMail extends Mailable
{
    use Queueable;

    public function __construct(
        public array $bookingDetails,
        public string $recipientName,
        public string $recipientRole,
        public int $hoursUntilSession,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->hoursUntilSession === 1
            ? 'Reminder: your Grads Paths session starts in 1 hour'
            : sprintf('Reminder: your Grads Paths session starts in %d hours', $this->hoursUntilSession);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'bookings::emails.booking-reminder',
            with: [
                'booking' => $this->bookingDetails,
                'recipientName' => $this->recipientName,
                'recipientRole' => $this->recipientRole,
                'hoursUntilSession' => $this->hoursUntilSession,
            ],
        );
    }
}

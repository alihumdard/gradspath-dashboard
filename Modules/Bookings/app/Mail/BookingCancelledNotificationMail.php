<?php

namespace Modules\Bookings\app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingCancelledNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public array $bookingDetails,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A Grads Paths booking has been cancelled',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'bookings::emails.booking-cancelled',
            with: [
                'booking' => $this->bookingDetails,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}

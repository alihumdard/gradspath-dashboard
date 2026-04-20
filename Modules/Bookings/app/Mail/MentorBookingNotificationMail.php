<?php

namespace Modules\Bookings\app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MentorBookingNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public array $bookingDetails,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A new booking was made with you',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'bookings::emails.mentor-booking-notification',
            with: [
                'booking' => $this->bookingDetails,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}

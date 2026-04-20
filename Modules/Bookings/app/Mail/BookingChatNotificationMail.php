<?php

namespace Modules\Bookings\app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingChatNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public array $chatDetails,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New message on your Grads Paths booking',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'bookings::emails.booking-chat-notification',
            with: [
                'chat' => $this->chatDetails,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}

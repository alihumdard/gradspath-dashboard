<?php

namespace Modules\Bookings\app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Bookings\app\Models\Chat;

class BookingChatMessageMail extends Mailable
{
    use Queueable;

    public function __construct(
        public Chat $chat,
        public string $chatUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New booking chat message from '.$this->chat->sender?->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'bookings::emails.booking-chat-message',
            with: [
                'chat' => $this->chat,
                'booking' => $this->chat->booking,
                'chatUrl' => $this->chatUrl,
            ],
        );
    }
}

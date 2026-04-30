<?php

namespace Modules\Bookings\app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ZoomReconnectRequiredMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $mentorName,
        public string $settingsUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reconnect Zoom to keep your Grads Paths bookings open');
    }

    public function content(): Content
    {
        return new Content(
            view: 'bookings::emails.zoom-reconnect-required',
            with: [
                'mentorName' => $this->mentorName,
                'settingsUrl' => $this->settingsUrl,
            ],
        );
    }
}

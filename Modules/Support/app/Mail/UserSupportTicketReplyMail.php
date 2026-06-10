<?php

namespace Modules\Support\app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Support\app\Models\SupportTicket;

class UserSupportTicketReplyMail extends Mailable
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on support ticket '.$this->ticket->ticket_ref,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'support::emails.user-ticket-reply',
            with: [
                'ticket' => $this->ticket,
                'supportUrl' => $this->supportUrl(),
            ],
        );
    }

    private function supportUrl(): string
    {
        return $this->ticket->user?->hasRole('mentor')
            ? route('mentor.support.index')
            : route('student.support.index');
    }
}

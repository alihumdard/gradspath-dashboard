<?php

namespace Modules\Support\app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Modules\Support\app\Models\SupportTicket;

class AdminNewSupportTicketMail extends Mailable
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New support ticket: '.$this->ticket->ticket_ref,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'support::emails.admin-new-ticket',
            with: [
                'ticket' => $this->ticket,
                'adminUrl' => route('admin.support.tickets.show', $this->ticket->id),
            ],
        );
    }
}

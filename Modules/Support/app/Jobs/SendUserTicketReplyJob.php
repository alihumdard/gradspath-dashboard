<?php

namespace Modules\Support\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Modules\Support\app\Mail\UserSupportTicketReplyMail;
use Modules\Support\app\Models\SupportTicket;

class SendUserTicketReplyJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $ticketId) {}

    public function handle(): void
    {
        $ticket = SupportTicket::query()
            ->with('user')
            ->find($this->ticketId);

        if (!$ticket?->user?->email || blank($ticket->admin_reply)) {
            return;
        }

        Mail::to($ticket->user->email)->send(new UserSupportTicketReplyMail($ticket));
    }
}

<?php

namespace Modules\Support\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\app\Models\User;
use Modules\Support\app\Mail\AdminNewSupportTicketMail;
use Modules\Support\app\Models\SupportTicket;

class NotifyAdminNewTicketJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $ticketId) {}

    public function handle(): void
    {
        $ticket = SupportTicket::query()
            ->with('user')
            ->find($this->ticketId);

        if (!$ticket) {
            return;
        }

        $adminEmails = User::role('admin')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique();

        if ($adminEmails->isEmpty() && config('mail.from.address')) {
            $adminEmails = collect([config('mail.from.address')]);
        }

        $adminEmails->each(fn (string $email) => Mail::to($email)->send(
            new AdminNewSupportTicketMail($ticket)
        ));
    }
}

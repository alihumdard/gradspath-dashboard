<?php

namespace Modules\Support\app\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Support\app\Events\SupportTicketCreated;
use Modules\Support\app\Models\SupportTicket;

class SupportTicketService
{
    public function create(User $user, array $data): SupportTicket
    {
        $raw = trim($data['message']);
        $sanitized = strip_tags($raw);

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'ticket_ref' => $this->generateTicketRef(),
            'subject' => trim($data['subject']),
            'message' => $sanitized,
            'message_raw' => $raw,
            'status' => 'open',
        ]);

        event(new SupportTicketCreated($ticket));

        return $ticket;
    }

    public function reply(SupportTicket $ticket, User $admin, string $reply, string $status = 'in_progress'): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $admin, $reply, $status) {
            $ticket->update([
                'admin_reply' => trim($reply),
                'status' => $status,
                'handled_by' => $admin->id,
                'replied_at' => now(),
            ]);

            return $ticket->fresh();
        });
    }

    private function generateTicketRef(): string
    {
        do {
            $ref = 'SUP-' . strtoupper(Str::random(6));
        } while (SupportTicket::query()->where('ticket_ref', $ref)->exists());

        return $ref;
    }
}

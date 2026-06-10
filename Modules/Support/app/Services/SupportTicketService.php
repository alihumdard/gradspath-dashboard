<?php

namespace Modules\Support\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Support\app\Jobs\SendUserTicketReplyJob;
use Modules\Support\app\Events\SupportTicketCreated;
use Modules\Support\app\Models\SupportTicket;

class SupportTicketService
{
    public function create(User $user, array $data): SupportTicket
    {
        return DB::transaction(function () use ($user, $data): SupportTicket {
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
        });
    }

    public function reply(SupportTicket $ticket, User $admin, string $reply, string $status = 'in_progress'): SupportTicket
    {
        $reply = trim($reply);

        $updatedTicket = DB::transaction(function () use ($ticket, $admin, $reply, $status) {
            $updates = [
                'status' => $status,
                'handled_by' => $admin->id,
            ];

            if ($reply !== '') {
                $updates['admin_reply'] = $reply;
                $updates['replied_at'] = now();
            }

            $ticket->update($updates);

            return $ticket->fresh();
        });

        if ($reply !== '') {
            SendUserTicketReplyJob::dispatch($updatedTicket->id);
        }

        return $updatedTicket;
    }

    private function generateTicketRef(): string
    {
        $latest = SupportTicket::query()
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first(['id']);

        return sprintf('SUP-%05d', ((int) ($latest?->id ?? 0)) + 1);
    }
}

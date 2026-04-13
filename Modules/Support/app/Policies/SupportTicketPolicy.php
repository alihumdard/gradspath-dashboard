<?php

namespace Modules\Support\app\Policies;

use Modules\Auth\app\Models\User;
use Modules\Support\app\Models\SupportTicket;

class SupportTicketPolicy
{
    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('admin') || (int) $ticket->user_id === (int) $user->id;
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('admin');
    }
}

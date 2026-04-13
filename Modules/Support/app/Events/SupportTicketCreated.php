<?php

namespace Modules\Support\app\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Support\app\Models\SupportTicket;

class SupportTicketCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public SupportTicket $ticket) {}
}

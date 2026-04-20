<?php

namespace Modules\Support\app\Listeners;

use Modules\Support\app\Events\SupportTicketCreated;
use Modules\Support\app\Jobs\NotifyAdminNewTicketJob;
use Modules\Support\app\Jobs\SendUserTicketConfirmationJob;

class DispatchSupportNotificationsListener
{
    public function handle(SupportTicketCreated $event): void
    {
        NotifyAdminNewTicketJob::dispatch($event->ticket->id);
        SendUserTicketConfirmationJob::dispatch($event->ticket->id);
    }
}

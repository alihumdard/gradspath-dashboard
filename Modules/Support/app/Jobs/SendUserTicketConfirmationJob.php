<?php

namespace Modules\Support\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendUserTicketConfirmationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $ticketId) {}

    public function handle(): void
    {
        Log::info('User support confirmation queued.', ['ticket_id' => $this->ticketId]);
    }
}

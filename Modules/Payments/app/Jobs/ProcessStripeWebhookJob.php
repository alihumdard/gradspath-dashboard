<?php

namespace Modules\Payments\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Payments\app\Services\StripeWebhookService;

class ProcessStripeWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $payload)
    {
    }

    public function handle(StripeWebhookService $webhooks): void
    {
        $webhooks->process($this->payload);
    }
}

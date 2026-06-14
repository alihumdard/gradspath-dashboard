<?php

namespace Modules\Payments\app\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Payments\app\Services\StripeWebhookService;

class ProcessStripeWebhookJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public function __construct(public array $payload) {}

    public function uniqueId(): string
    {
        return (string) ($this->payload['id'] ?? md5(json_encode($this->payload)));
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(StripeWebhookService $webhooks): void
    {
        $webhooks->process($this->payload);
    }
}

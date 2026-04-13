<?php

namespace Modules\Payments\app\Console;

use Illuminate\Console\Command;
use Modules\Payments\app\Models\StripeWebhook;
use Modules\Payments\app\Services\StripeWebhookService;

class RetryStripeWebhooksCommand extends Command
{
    protected $signature = 'stripe:retry-webhooks';

    protected $description = 'Retry unprocessed Stripe webhook events.';

    public function handle(StripeWebhookService $service): int
    {
        $pending = StripeWebhook::query()
            ->where('processed', false)
            ->orderBy('received_at')
            ->limit(50)
            ->get();

        $processed = 0;
        foreach ($pending as $webhook) {
            try {
                $service->process($webhook->payload ?? []);
                $processed++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Retried webhooks: {$processed}");

        return self::SUCCESS;
    }
}

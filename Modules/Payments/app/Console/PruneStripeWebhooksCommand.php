<?php

namespace Modules\Payments\app\Console;

use Illuminate\Console\Command;
use Modules\Payments\app\Models\StripeWebhook;

class PruneStripeWebhooksCommand extends Command
{
    protected $signature = 'stripe:prune-webhooks {--days=}';

    protected $description = 'Prune old processed Stripe webhook rows.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('payments.stripe_webhook_retention_days', 90));
        $days = max($days, 1);

        $deleted = StripeWebhook::query()
            ->where('processed', true)
            ->where('received_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Pruned Stripe webhooks: {$deleted}");

        return self::SUCCESS;
    }
}

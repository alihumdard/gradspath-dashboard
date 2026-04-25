<?php

namespace Modules\Payments\app\Console;

use Illuminate\Console\Command;
use Modules\Payments\app\Services\MentorPayoutService;

class RetryMentorPayoutsCommand extends Command
{
    protected $signature = 'payments:retry-mentor-payouts {--limit=50}';

    protected $description = 'Retry eligible mentor payout transfers.';

    public function handle(MentorPayoutService $service): int
    {
        $processed = $service->retryEligiblePayouts((int) $this->option('limit'));

        $this->info("Retried mentor payouts: {$processed}");

        return self::SUCCESS;
    }
}

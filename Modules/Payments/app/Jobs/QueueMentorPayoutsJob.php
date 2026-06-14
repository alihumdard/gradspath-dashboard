<?php

namespace Modules\Payments\app\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Payments\app\Models\MentorPayout;

class QueueMentorPayoutsJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public int $uniqueFor = 240;

    public function __construct(public int $limit = 50) {}

    public function uniqueId(): string
    {
        return 'mentor-payout-scan';
    }

    public function handle(): void
    {
        foreach ($this->eligiblePayoutIds() as $payoutId) {
            ProcessMentorPayoutJob::dispatch($payoutId);
        }
    }

    private function eligiblePayoutIds(): array
    {
        return MentorPayout::query()
            ->whereIn('status', [
                MentorPayout::STATUS_READY,
                MentorPayout::STATUS_FAILED,
            ])
            ->where('attempt_count', '<', $this->retryLimit())
            ->orderBy('id')
            ->limit($this->limit)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function retryLimit(): int
    {
        return max((int) config('payments.mentor_payout_retry_limit', 5), 1);
    }
}

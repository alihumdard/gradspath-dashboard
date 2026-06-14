<?php

namespace Modules\Payments\app\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Payments\app\Models\MentorPayout;
use Modules\Payments\app\Services\MentorPayoutService;

class ProcessMentorPayoutJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public function __construct(public int $payoutId) {}

    public function uniqueId(): string
    {
        return (string) $this->payoutId;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(MentorPayoutService $payouts): void
    {
        $payout = MentorPayout::query()
            ->with(['booking.mentor'])
            ->find($this->payoutId);

        if (! $payout || ! in_array((string) $payout->status, [
            MentorPayout::STATUS_READY,
            MentorPayout::STATUS_FAILED,
        ], true)) {
            return;
        }

        if ((int) $payout->attempt_count >= $this->retryLimit()) {
            return;
        }

        $booking = $payout->booking;

        if (! $booking || $booking->status !== 'completed') {
            return;
        }

        $payouts->releaseForCompletedBooking($booking);
    }

    private function retryLimit(): int
    {
        return max((int) config('payments.mentor_payout_retry_limit', 5), 1);
    }
}

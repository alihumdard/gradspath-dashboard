<?php

namespace Modules\Bookings\app\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Bookings\app\Services\MarkCompletedBookingsService;

class MarkCompletedBookingsJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public int $uniqueFor = 840;

    public function uniqueId(): string
    {
        return 'mark-completed-bookings';
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(MarkCompletedBookingsService $bookings): void
    {
        $bookings->process();
    }
}

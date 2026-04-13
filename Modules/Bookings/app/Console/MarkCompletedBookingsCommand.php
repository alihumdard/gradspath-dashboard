<?php

namespace Modules\Bookings\app\Console;

use Illuminate\Console\Command;
use Modules\Bookings\app\Models\Booking;

class MarkCompletedBookingsCommand extends Command
{
    protected $signature = 'bookings:mark-completed';

    protected $description = 'Mark past confirmed bookings as completed and set feedback due window.';

    public function handle(): int
    {
        $count = Booking::query()
            ->where('status', 'confirmed')
            ->where('session_at', '<=', now())
            ->update([
                'status' => 'completed',
                'feedback_due_at' => now()->addDay(),
            ]);

        $this->info("Bookings marked completed: {$count}");

        return self::SUCCESS;
    }
}

<?php

namespace Modules\Bookings\app\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\Bookings\app\Jobs\SendBookingReminderJob;
use Modules\Bookings\app\Models\Booking;

class DispatchBookingRemindersCommand extends Command
{
    protected $signature = 'bookings:send-reminders
        {--hours=24 : Reminder offset in hours}';

    protected $description = 'Queue booking reminder emails for upcoming confirmed Zoom sessions.';

    public function handle(): int
    {
        $hours = max((int) $this->option('hours'), 1);
        $windowStart = now()->utc()->addHours($hours)->subMinutes(15);
        $windowEnd = now()->utc()->addHours($hours)->addMinutes(15);

        $bookings = Booking::query()
            ->where('status', 'confirmed')
            ->where('session_type', '!=', 'office_hours')
            ->whereNotNull('session_at')
            ->whereBetween('session_at', [$windowStart, $windowEnd])
            ->with(['booker', 'mentor.user', 'service', 'participantRecords'])
            ->orderBy('session_at')
            ->get();

        $queued = 0;

        foreach ($bookings as $booking) {
            $cacheKey = sprintf('booking-reminder:%d:%d', $hours, $booking->id);

            if (! Cache::add($cacheKey, true, $booking->session_at?->copy()->addDay() ?? now()->addDay())) {
                continue;
            }

            SendBookingReminderJob::dispatch($booking->id, $hours);
            $queued++;
        }

        $this->info(sprintf('Queued %d booking reminder(s) for the %d-hour window.', $queued, $hours));

        return self::SUCCESS;
    }
}

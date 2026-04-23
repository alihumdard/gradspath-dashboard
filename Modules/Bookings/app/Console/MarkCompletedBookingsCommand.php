<?php

namespace Modules\Bookings\app\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingAttendanceResolver;

class MarkCompletedBookingsCommand extends Command
{
    protected $signature = 'bookings:mark-completed';

    protected $description = 'Mark past confirmed bookings as completed and set feedback due window.';

    public function __construct(private readonly BookingAttendanceResolver $attendance)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = 0;

        Booking::query()
            ->where('status', 'confirmed')
            ->whereNotNull('session_at')
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $scheduledEnd = $booking->session_at?->copy()->addMinutes(max((int) $booking->duration_minutes, 1));

                    if (! $scheduledEnd || $scheduledEnd->isFuture()) {
                        continue;
                    }

                    $feedbackUnlockedAt = $booking->feedback_unlocked_at
                        ?? $scheduledEnd->copy()->addMinutes((int) config('services.zoom.attendance.feedback_fallback_grace_minutes', 60));

                    $booking->forceFill([
                        'status' => 'completed',
                        'completed_at' => $booking->completed_at ?? $scheduledEnd,
                        'completion_source' => $booking->completion_source ?? 'schedule',
                        'session_outcome' => $booking->session_outcome ?? 'completed',
                        'feedback_due_at' => $booking->feedback_due_at ?? Carbon::now()->addDay(),
                        'feedback_unlocked_at' => $feedbackUnlockedAt,
                    ])->save();

                    $this->attendance->refresh($booking);
                    $count++;
                }
            });

        $this->info("Bookings marked completed: {$count}");

        return self::SUCCESS;
    }
}

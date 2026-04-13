<?php

namespace Modules\Bookings\app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Bookings\app\Models\Booking;

class SendBookingConfirmationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $bookingId) {}

    public function handle(): void
    {
        $booking = Booking::query()->with(['student', 'mentor.user'])->find($this->bookingId);
        if (!$booking) {
            return;
        }

        Log::info('Booking confirmation queued.', ['booking_id' => $this->bookingId]);
    }
}

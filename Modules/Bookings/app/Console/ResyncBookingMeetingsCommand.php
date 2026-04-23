<?php

namespace Modules\Bookings\app\Console;

use Illuminate\Console\Command;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingMeetingSyncService;

class ResyncBookingMeetingsCommand extends Command
{
    protected $signature = 'bookings:resync-meetings
        {booking_ids?* : Specific booking IDs to resync}
        {--failed : Only retry bookings currently marked as failed}';

    protected $description = 'Retry Zoom meeting sync for existing bookings.';

    public function __construct(private readonly BookingMeetingSyncService $meetingSync)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $bookings = $this->targetBookings();

        if ($bookings->isEmpty()) {
            $this->warn('No bookings matched the resync criteria.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Resyncing %d booking(s)...', $bookings->count()));

        $synced = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($bookings as $booking) {
            $result = $this->meetingSync->syncCreatedBooking($booking);

            $status = (string) $result->calendar_sync_status;
            $link = (string) ($result->meeting_link ?? '');

            if ($status === 'synced' && $link !== '') {
                $synced++;
                $this->line(sprintf(
                    '  [synced] Booking #%d -> %s',
                    $result->id,
                    $link
                ));

                continue;
            }

            if ($status === 'skipped') {
                $skipped++;
                $this->line(sprintf(
                    '  [skipped] Booking #%d -> %s',
                    $result->id,
                    (string) ($result->calendar_last_error ?? 'Skipped by sync rules.')
                ));

                continue;
            }

            $failed++;
            $this->line(sprintf(
                '  [failed] Booking #%d -> %s',
                $result->id,
                (string) ($result->calendar_last_error ?? 'Unknown sync failure.')
            ));
        }

        $this->newLine();
        $this->info("Synced: {$synced}");
        $this->warn("Skipped: {$skipped}");

        if ($failed > 0) {
            $this->error("Failed: {$failed}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function targetBookings()
    {
        $query = Booking::query()
            ->with(['booker', 'mentor.user', 'participantRecords', 'service'])
            ->where('session_type', '!=', 'office_hours')
            ->where('status', 'confirmed')
            ->where('session_at', '>', now())
            ->orderBy('id');

        $bookingIds = collect((array) $this->argument('booking_ids'))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($bookingIds->isNotEmpty()) {
            $query->whereIn('id', $bookingIds->all());
        } elseif ($this->option('failed')) {
            $query->where('calendar_sync_status', 'failed');
        } else {
            $query->whereIn('calendar_sync_status', ['failed', 'not_synced', 'skipped']);
        }

        return $query->get();
    }
}

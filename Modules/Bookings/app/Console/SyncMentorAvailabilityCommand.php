<?php

namespace Modules\Bookings\app\Console;

use Illuminate\Console\Command;
use Modules\Bookings\app\Services\MentorAvailabilityManagerService;

class SyncMentorAvailabilityCommand extends Command
{
    protected $signature = 'bookings:sync-availability';

    protected $description = 'Sync recurring mentor availability into future booking slots.';

    public function __construct(private readonly MentorAvailabilityManagerService $availability)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = $this->availability->syncAllMentors();

        $this->info("Availability slots synced: {$count}");

        return self::SUCCESS;
    }
}

<?php

namespace Modules\OfficeHours\app\Console;

use Illuminate\Console\Command;
use Modules\OfficeHours\app\Services\OfficeHourSessionSyncService;

class SyncOfficeHourSessionsCommand extends Command
{
    protected $signature = 'office-hours:sync-sessions';

    protected $description = 'Generate the next weekly office-hours session for active mentor schedules.';

    public function handle(OfficeHourSessionSyncService $sessions): int
    {
        $created = $sessions->syncUpcomingWeeklySessions();

        $this->info("Created {$created} office-hours session(s).");

        return self::SUCCESS;
    }
}

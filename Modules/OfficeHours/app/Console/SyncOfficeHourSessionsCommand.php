<?php

namespace Modules\OfficeHours\app\Console;

use Illuminate\Console\Command;
use Modules\OfficeHours\app\Services\OfficeHourSessionSyncService;

class SyncOfficeHourSessionsCommand extends Command
{
    protected $signature = 'office-hours:sync-sessions {--test-interval= : Local/testing only. Generate the next session this many minutes from now.}';

    protected $description = 'Generate the next weekly office-hours session for active mentor schedules.';

    public function handle(OfficeHourSessionSyncService $sessions): int
    {
        $testInterval = $this->option('test-interval');

        if ($testInterval !== null && ! app()->environment(['local', 'testing'])) {
            $this->error('The --test-interval option is only allowed in local/testing environments.');

            return self::FAILURE;
        }

        $created = $testInterval !== null
            ? $sessions->syncTestingSessions((int) $testInterval)
            : $sessions->syncUpcomingWeeklySessions();

        $this->info("Created {$created} office-hours session(s).");

        return self::SUCCESS;
    }
}

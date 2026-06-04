<?php

namespace Modules\Institutions\app\Console;

use Illuminate\Console\Command;
use Modules\Discovery\app\Services\TopInstitutionService;

class RefreshFeaturedInstitutionsCommand extends Command
{
    protected $signature = 'institutions:refresh-featured {--limit=5 : Number of automatic featured institutions to store}';

    protected $description = 'Refresh automatic featured institutions by mentor meeting count.';

    public function handle(TopInstitutionService $institutions): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $featured = $institutions->refreshAutomatic($limit);

        $this->info("Featured institutions refreshed: {$featured->count()}");

        $featured->each(function ($institution): void {
            $this->line(sprintf(
                '%s - %d meetings',
                $institution->display_name ?: $institution->name,
                (int) $institution->bookings_count
            ));
        });

        return self::SUCCESS;
    }
}

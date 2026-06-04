<?php

namespace Modules\Institutions\app\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Institutions\app\Console\ImportUniversitiesCommand;
use Modules\Institutions\app\Console\RefreshFeaturedInstitutionsCommand;

class InstitutionsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Institutions';
    protected string $moduleNameLower = 'institutions';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->moduleNameLower);
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->commands([
            ImportUniversitiesCommand::class,
            RefreshFeaturedInstitutionsCommand::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('institutions:refresh-featured')->dailyAt('00:30');
        });
    }

    public function register(): void
    {
        //
    }
}

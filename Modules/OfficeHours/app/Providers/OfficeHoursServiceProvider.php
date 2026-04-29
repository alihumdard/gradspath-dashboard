<?php

namespace Modules\OfficeHours\app\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\OfficeHours\app\Console\SyncOfficeHourSessionsCommand;

class OfficeHoursServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'OfficeHours';
    protected string $moduleNameLower = 'office-hours';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->moduleNameLower);
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->commands([
            SyncOfficeHourSessionsCommand::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $testInterval = (int) env('OFFICE_HOURS_TEST_ROTATION_MINUTES', 0);

            if (app()->environment('local') && $testInterval > 0) {
                $schedule->command("office-hours:sync-sessions --test-interval={$testInterval}")->everyTwoMinutes();

                return;
            }

            $schedule->command('office-hours:sync-sessions')->dailyAt('00:30');
        });
    }

    public function register(): void
    {
        //
    }
}

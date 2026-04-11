<?php

namespace Modules\OfficeHours\app\Providers;

use Illuminate\Support\ServiceProvider;

class OfficeHoursServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'OfficeHours';
    protected string $moduleNameLower = 'office-hours';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->moduleNameLower);
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function register(): void
    {
        //
    }
}

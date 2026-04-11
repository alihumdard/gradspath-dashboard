<?php

namespace Modules\Discovery\app\Providers;

use Illuminate\Support\ServiceProvider;

class DiscoveryServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Discovery';
    protected string $moduleNameLower = 'discovery';

    public function boot(): void
    {
        // Register module views — accessible as discovery::mentor.dashboard, etc.
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->moduleNameLower);

        // Register module routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Register module migrations if any
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function register(): void
    {
        //
    }
}

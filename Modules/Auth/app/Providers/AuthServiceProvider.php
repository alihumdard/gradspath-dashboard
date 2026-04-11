<?php

namespace Modules\Auth\app\Providers;

use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Auth';
    protected string $moduleNameLower = 'auth';

    public function boot(): void
    {
        // Register module views — accessible as auth::login, auth::register etc.
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->moduleNameLower);

        // Register module routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Register module migrations (auto-discovered via config, but explicit is safer)
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function register(): void
    {
        //
    }
}

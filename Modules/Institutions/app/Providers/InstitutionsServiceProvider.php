<?php

namespace Modules\Institutions\app\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Institutions\app\Console\ImportUniversitiesCommand;

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
        ]);
    }

    public function register(): void
    {
        //
    }
}

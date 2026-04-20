<?php

namespace Modules\MentorNotes\app\Providers;

use Illuminate\Support\ServiceProvider;

class MentorNotesServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'MentorNotes';
    protected string $moduleNameLower = 'mentor-notes';

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

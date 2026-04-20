<?php

namespace Modules\Support\app\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Support\app\Events\SupportTicketCreated;
use Modules\Support\app\Listeners\DispatchSupportNotificationsListener;

class SupportServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Support';
    protected string $moduleNameLower = 'support';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->moduleNameLower);
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        Event::listen(SupportTicketCreated::class, DispatchSupportNotificationsListener::class);
    }

    public function register(): void
    {
        //
    }
}

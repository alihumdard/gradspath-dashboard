<?php

namespace Modules\Payments\app\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Payments\app\Console\RetryMentorPayoutsCommand;
use Modules\Payments\app\Console\RetryStripeWebhooksCommand;

class PaymentsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Payments';
    protected string $moduleNameLower = 'payments';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->moduleNameLower);
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->commands([
            RetryMentorPayoutsCommand::class,
            RetryStripeWebhooksCommand::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('stripe:retry-webhooks')->everyFiveMinutes();
            $schedule->command('payments:retry-mentor-payouts')->everyFiveMinutes();
        });
    }

    public function register(): void
    {
        //
    }
}

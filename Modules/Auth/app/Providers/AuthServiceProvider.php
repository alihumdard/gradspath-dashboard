<?php

namespace Modules\Auth\app\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Policies\BookingPolicy;
use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Policies\FeedbackPolicy;
use Modules\Support\app\Models\SupportTicket;
use Modules\Support\app\Policies\SupportTicketPolicy;

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

        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Feedback::class, FeedbackPolicy::class);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'auth-module');
    }
}

<?php

namespace Modules\Bookings\app\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Bookings\app\Console\MarkCompletedBookingsCommand;
use Modules\Bookings\app\Console\SyncMentorAvailabilityCommand;
use Modules\Bookings\app\Events\BookingCancelled;
use Modules\Bookings\app\Events\BookingCreated;
use Modules\Bookings\app\Listeners\GenerateMeetingLinkListener;
use Modules\Bookings\app\Listeners\HandleBookingCancelledListener;

class BookingsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Bookings';
    protected string $moduleNameLower = 'bookings';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->moduleNameLower);
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        Event::listen(BookingCreated::class, GenerateMeetingLinkListener::class);
        Event::listen(BookingCancelled::class, HandleBookingCancelledListener::class);

        $this->commands([
            MarkCompletedBookingsCommand::class,
            SyncMentorAvailabilityCommand::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('bookings:mark-completed')->everyFifteenMinutes();
            $schedule->command('bookings:sync-availability')->dailyAt('00:15');
        });
    }

    public function register(): void
    {
        //
    }
}

<?php

namespace Modules\Feedback\app\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Feedback\app\Events\FeedbackSubmitted;
use Modules\Feedback\app\Listeners\UpdateMentorRatingListener;

class FeedbackServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Feedback';
    protected string $moduleNameLower = 'feedback';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', $this->moduleNameLower);
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        Event::listen(FeedbackSubmitted::class, UpdateMentorRatingListener::class);
    }

    public function register(): void
    {
        //
    }
}

<?php

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        RedirectIfAuthenticated::redirectUsing(function ($request): string {
            $user = Auth::user();

            if ($user?->hasRole('admin')) {
                return route('admin.dashboard');
            }

            if ($user?->hasRole('mentor')) {
                return route('mentor.dashboard');
            }

            if ($user?->hasRole('student')) {
                return route('student.dashboard');
            }

            return '/';
        });

        $middleware->redirectGuestsTo(function ($request): string {
            return $request->is('admin') || $request->is('admin/*')
                ? route('admin.login')
                : route('login');
        });

        // Spatie role/permission middleware aliases
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

            // Module-scoped middleware aliases
            'active'           => \Modules\Auth\app\Http\Middleware\EnsureActiveAccount::class,
            'booking.participant' => \Modules\Bookings\app\Http\Middleware\EnsureBookingParticipant::class,

            // Legacy aliases still used in some routes/modules
            'mentor.approved'  => \App\Http\Middleware\EnsureMentorApproved::class,
            'feedback.required' => \App\Http\Middleware\EnsureFeedbackCompleted::class,
        ]);

        // Exclude Stripe webhook routes from CSRF — they are POST-ed by Stripe servers, not browsers
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
            'webhooks/stripe/connect',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

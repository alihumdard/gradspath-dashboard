<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');

        // Spatie role/permission middleware aliases
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

            // Custom middleware (to be created in app/Http/Middleware/)
            'active'           => \App\Http\Middleware\EnsureUserIsActive::class,
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

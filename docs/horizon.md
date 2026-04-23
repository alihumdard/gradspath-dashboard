# Horizon Operations

Laravel Horizon is installed in the project, but it is currently disabled in app bootstrapping for now.

## Local Development

- Start the app with `composer dev`. This currently runs `php artisan queue:listen --tries=1` instead of Horizon.
- Ensure Redis is running locally before dispatching queued jobs.
- The Horizon dashboard is currently disabled because the Horizon service provider is commented out in `bootstrap/providers.php`.

## Production

- If Horizon remains disabled, run `php artisan queue:work` or `php artisan queue:listen` under your process manager instead of `php artisan horizon`.
- Keep `QUEUE_CONNECTION=redis` in the production environment.
- If Horizon is re-enabled later, restore the provider registration and use the Horizon env values from `.env.example`.

## Failure Notes

- If Redis is unavailable, queued jobs on the `redis` connection will not be processed.
- Failed jobs continue to use the existing `failed_jobs` database table.

# Horizon Operations

Laravel Horizon is installed and configured to run Redis-backed queues for this app.

## Local Development

- Start the app with `composer dev`. This now runs `php artisan horizon` instead of `queue:listen`.
- Ensure Redis is running locally before dispatching queued jobs.
- The Horizon dashboard is available at `/horizon` for authenticated, active admins only.

## Production

- Run `php artisan horizon` under your process manager instead of `php artisan queue:work` or `php artisan queue:listen`.
- Keep `QUEUE_CONNECTION=redis` in the production environment.
- Tune the Horizon env values from `.env.example` if you need more worker capacity.

## Failure Notes

- If Redis is unavailable, Horizon will not be able to process queued jobs.
- Failed jobs continue to use the existing `failed_jobs` database table.

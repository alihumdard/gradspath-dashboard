# Horizon Operations

Laravel Horizon is installed and enabled for Redis-backed queues.

## Local Development

- Start the app with `composer dev`. This runs `php artisan horizon` alongside the web server and Vite.
- Ensure Redis is running locally before dispatching queued jobs.
- The Horizon dashboard is available at `/horizon` for authenticated active admins.

## Production

- Keep `QUEUE_CONNECTION=redis` in the production environment.
- Run `php artisan horizon` under Supervisor or systemd on the VPS.
- Run Laravel's scheduler every minute so Horizon metrics are captured by `horizon:snapshot`.
- During deployments, run `php artisan horizon:terminate` after releasing new code so the process manager restarts Horizon with the latest code.

Example Supervisor program:

```ini
[program:gradspath-horizon]
process_name=%(program_name)s
command=php /path/to/current/artisan horizon
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/current/storage/logs/horizon.log
```

## Failure Notes

- If Redis is unavailable, queued jobs on the `redis` connection will not be processed.
- Failed jobs continue to use the existing `failed_jobs` database table.

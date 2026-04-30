<?php

namespace Modules\Bookings\app\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\app\Models\OauthToken;
use Modules\Bookings\app\Mail\ZoomReconnectRequiredMail;
use Modules\Bookings\app\Services\ZoomService;
use Modules\Settings\app\Models\Mentor;

class RefreshMentorZoomTokensCommand extends Command
{
    protected $signature = 'zoom:refresh-mentor-tokens';

    protected $description = 'Refresh active mentor Zoom OAuth tokens and notify mentors who need to reconnect.';

    public function __construct(private readonly ZoomService $zoom)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->zoom->isConfigured()) {
            $this->warn('Zoom OAuth is not configured. Skipping mentor token refresh.');

            return self::SUCCESS;
        }

        $mentors = Mentor::query()
            ->where('status', 'active')
            ->with('user.oauthTokens')
            ->orderBy('id')
            ->get();

        $refreshed = 0;
        $reconnectRequired = 0;
        $temporaryFailures = 0;
        $skipped = 0;

        foreach ($mentors as $mentor) {
            $user = $mentor->user;

            if (! $user) {
                $skipped++;
                continue;
            }

            $token = $user->oauthTokens
                ->first(fn (OauthToken $token) => $token->provider === 'zoom');

            if (! $token) {
                $skipped++;
                continue;
            }

            if (trim((string) $token->refresh_token) === '') {
                $reconnectRequired++;
                $this->notifyReconnectRequired($mentor, 'missing_refresh_token');
                continue;
            }

            try {
                $this->zoom->refreshToken($token);
                Cache::forget($this->notificationCacheKey((int) $user->id));
                $refreshed++;
            } catch (\RuntimeException $exception) {
                if (str_contains($exception->getMessage(), 'reconnect Zoom') || str_contains($exception->getMessage(), 'revoked')) {
                    $reconnectRequired++;
                    $this->notifyReconnectRequired($mentor, $exception->getMessage());
                    continue;
                }

                $temporaryFailures++;
                Log::warning('Scheduled Zoom token refresh temporarily failed.', [
                    'mentor_id' => $mentor->id,
                    'user_id' => $user->id,
                    'reason' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("Zoom token refresh complete. Refreshed: {$refreshed}; reconnect required: {$reconnectRequired}; temporary failures: {$temporaryFailures}; skipped: {$skipped}.");

        return self::SUCCESS;
    }

    private function notifyReconnectRequired(Mentor $mentor, string $reason): void
    {
        $user = $mentor->user;

        if (! $user?->email) {
            return;
        }

        Log::warning('Mentor Zoom reconnect required.', [
            'mentor_id' => $mentor->id,
            'user_id' => $user->id,
            'reason' => $reason,
        ]);

        if (! Cache::add($this->notificationCacheKey((int) $user->id), true, now()->addDays(7))) {
            return;
        }

        Mail::to($user->email)->send(new ZoomReconnectRequiredMail(
            mentorName: $user->name ?: 'Mentor',
            settingsUrl: route('mentor.settings.index'),
        ));
    }

    private function notificationCacheKey(int $userId): string
    {
        return "zoom-reconnect-required-notified:{$userId}";
    }
}

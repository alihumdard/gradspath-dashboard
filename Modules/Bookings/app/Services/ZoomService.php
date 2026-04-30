<?php

namespace Modules\Bookings\app\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Auth\app\Models\OauthToken;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;

class ZoomService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.zoom.enabled', false)
            && $this->clientId() !== null
            && $this->clientSecret() !== null
            && $this->redirectUri() !== null;
    }

    public function authorizationUrl(string $state): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Zoom OAuth is not fully configured.');
        }

        return $this->authorizeUrl().'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
        ]);
    }

    public function connectUser(User $user, string $code): OauthToken
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Zoom OAuth is not fully configured.');
        }

        Log::info('Exchanging Zoom authorization code for user token.', [
            'user_id' => $user->id,
        ]);

        $payload = $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
        ]);

        $profile = $this->retrieveCurrentUserProfile((string) ($payload['access_token'] ?? ''));

        return $this->storeUserToken($user, $payload, $profile);
    }

    public function disconnectUser(User $user): void
    {
        OauthToken::query()
            ->where('user_id', $user->id)
            ->where('provider', 'zoom')
            ->delete();
    }

    public function oauthTokenForUser(User $user): ?OauthToken
    {
        return OauthToken::query()
            ->where('user_id', $user->id)
            ->where('provider', 'zoom')
            ->first();
    }

    public function connectionStatusForUser(User $user): string
    {
        $token = $this->oauthTokenForUser($user);

        if (! $token) {
            return 'not_connected';
        }

        if (trim((string) $token->refresh_token) === '') {
            return 'error';
        }

        return 'connected';
    }

    public function hasConnectedUser(User $user): bool
    {
        $token = $this->oauthTokenForUser($user);

        if (! $token) {
            return false;
        }

        return trim((string) $token->refresh_token) !== '';
    }

    public function hasConnectedMentor(?Mentor $mentor): bool
    {
        return $mentor?->user ? $this->hasConnectedUser($mentor->user) : false;
    }

    public function assertUserConnectionIsUsable(User $user): void
    {
        $token = $this->oauthTokenForUser($user);

        if (! $token) {
            throw new \RuntimeException('This mentor has not connected Zoom.');
        }

        if (trim((string) $token->refresh_token) === '') {
            throw new \RuntimeException('Zoom refresh token is missing. Please reconnect Zoom.');
        }

        $accessToken = $this->accessTokenForUser($user);

        try {
            $this->retrieveCurrentUserProfile($accessToken);
        } catch (RequestException $exception) {
            if (in_array($exception->response->status(), [401, 403], true)) {
                $token = $this->oauthTokenForUser($user);

                if ($token && trim((string) $token->refresh_token) !== '') {
                    $token = $this->refreshToken($token);

                    try {
                        $this->retrieveCurrentUserProfile((string) $token->access_token);

                        return;
                    } catch (RequestException $retryException) {
                        if (! in_array($retryException->response->status(), [401, 403], true)) {
                            throw new \RuntimeException('Zoom connection check temporarily failed. Please try again shortly.', 0, $retryException);
                        }

                        $exception = $retryException;
                    }
                }

                $this->clearUserToken($user);

                throw new \RuntimeException('Zoom connection expired or was revoked. Please reconnect Zoom.', 0, $exception);
            }

            throw new \RuntimeException('Zoom connection check temporarily failed. Please try again shortly.', 0, $exception);
        }
    }

    public function assertMentorConnectionIsUsable(?Mentor $mentor): void
    {
        if (! $mentor?->user) {
            throw new \RuntimeException('The booking mentor user could not be resolved.');
        }

        $this->assertUserConnectionIsUsable($mentor->user);
    }

    public function createMeeting(Booking $booking): array
    {
        $mentorUser = $this->mentorUserForBooking($booking);
        $payload = $this->meetingPayload($booking);

        Log::info('Zoom meeting create request prepared.', [
            'booking_id' => $booking->id,
            'mentor_id' => $booking->mentor_id,
            'mentor_user_id' => $mentorUser->id,
            'student_id' => $booking->student_id,
            'service_config_id' => $booking->service_config_id,
            'session_type' => $booking->session_type,
            'session_at_utc' => optional($booking->session_at)?->toIso8601String(),
            'session_timezone' => $booking->session_timezone,
            'duration_minutes' => $booking->duration_minutes,
            'topic' => $payload['topic'] ?? null,
            'start_time' => $payload['start_time'] ?? null,
            'timezone' => $payload['timezone'] ?? null,
            'settings' => $payload['settings'] ?? [],
            'api_base' => $this->apiBase(),
        ]);

        $response = Http::withToken($this->accessTokenForUser($mentorUser))
            ->acceptJson()
            ->post($this->apiBase().'/users/me/meetings', $payload)
            ->throw();

        $meeting = $response->json();

        Log::info('Zoom meeting create response received.', [
            'booking_id' => $booking->id,
            'mentor_user_id' => $mentorUser->id,
            'status' => $response->status(),
            'meeting_id' => data_get($meeting, 'id'),
            'uuid_present' => filled(data_get($meeting, 'uuid')),
            'host_id_present' => filled(data_get($meeting, 'host_id')),
            'host_email_present' => filled(data_get($meeting, 'host_email')),
            'start_url_present' => filled(data_get($meeting, 'start_url')),
            'join_url_present' => filled(data_get($meeting, 'join_url')),
            'join_url_host' => parse_url((string) data_get($meeting, 'join_url'), PHP_URL_HOST),
            'response_keys' => array_keys(is_array($meeting) ? $meeting : []),
        ]);

        return $meeting;
    }

    public function cancelMeeting(Booking $booking): void
    {
        $mentorUser = $this->mentorUserForBooking($booking);
        $meetingId = (string) $booking->external_calendar_event_id;

        Log::info('Zoom meeting cancellation request prepared.', [
            'booking_id' => $booking->id,
            'mentor_user_id' => $mentorUser->id,
            'meeting_id' => $meetingId,
            'api_base' => $this->apiBase(),
        ]);

        $response = Http::withToken($this->accessTokenForUser($mentorUser))
            ->acceptJson()
            ->delete($this->apiBase().'/meetings/'.$meetingId)
            ->throw();

        Log::info('Zoom meeting cancellation response received.', [
            'booking_id' => $booking->id,
            'mentor_user_id' => $mentorUser->id,
            'meeting_id' => $meetingId,
            'status' => $response->status(),
        ]);
    }

    public function getMeeting(Booking $booking): array
    {
        $mentorUser = $this->mentorUserForBooking($booking);
        $meetingId = (string) $booking->external_calendar_event_id;

        Log::info('Zoom meeting retrieve request prepared.', [
            'booking_id' => $booking->id,
            'mentor_user_id' => $mentorUser->id,
            'meeting_id' => $meetingId,
            'api_base' => $this->apiBase(),
        ]);

        $response = Http::withToken($this->accessTokenForUser($mentorUser))
            ->acceptJson()
            ->get($this->apiBase().'/meetings/'.$meetingId)
            ->throw();

        $meeting = $response->json();

        Log::info('Zoom meeting retrieve response received.', [
            'booking_id' => $booking->id,
            'mentor_user_id' => $mentorUser->id,
            'meeting_id' => data_get($meeting, 'id') ?: $meetingId,
            'status' => $response->status(),
            'start_url_present' => filled(data_get($meeting, 'start_url')),
            'join_url_present' => filled(data_get($meeting, 'join_url')),
            'host_email_present' => filled(data_get($meeting, 'host_email')),
            'response_keys' => array_keys(is_array($meeting) ? $meeting : []),
        ]);

        return is_array($meeting) ? $meeting : [];
    }

    public function accessTokenForUser(User $user): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Zoom OAuth is not fully configured.');
        }

        $token = $this->oauthTokenForUser($user);

        if (! $token) {
            throw new \RuntimeException('This mentor has not connected Zoom.');
        }

        if ($token->token_expires_at?->isPast() || trim((string) $token->access_token) === '') {
            $token = $this->refreshToken($token);
        }

        $accessToken = trim((string) $token->access_token);

        if ($accessToken === '') {
            throw new \RuntimeException('Zoom did not return an access token.');
        }

        return $accessToken;
    }

    public function refreshToken(OauthToken $token): OauthToken
    {
        $refreshToken = trim((string) $token->refresh_token);

        if ($refreshToken === '') {
            throw new \RuntimeException('Zoom refresh token is missing. Please reconnect Zoom.');
        }

        Log::info('Refreshing Zoom OAuth token for user.', [
            'user_id' => $token->user_id,
            'provider_user_id' => $token->provider_user_id,
        ]);

        try {
            $payload = $this->tokenRequest([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);
        } catch (\Throwable $exception) {
            $shouldReconnect = $this->refreshFailureRequiresReconnect($exception);

            Log::warning('Zoom OAuth token refresh failed.', array_filter([
                'user_id' => $token->user_id,
                'provider_user_id' => $token->provider_user_id,
                'exception_class' => $exception::class,
                'status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'zoom_error' => $exception instanceof RequestException ? data_get($exception->response->json(), 'error') : null,
                'zoom_reason' => $exception instanceof RequestException ? data_get($exception->response->json(), 'reason') : null,
                'requires_reconnect' => $shouldReconnect,
            ], fn ($value) => $value !== null));

            if ($shouldReconnect) {
                $this->clearUserToken(User::query()->findOrFail($token->user_id));

                throw new \RuntimeException('Zoom connection expired or was revoked. Please reconnect Zoom.', 0, $exception);
            }

            $token->forceFill([
                'token_expires_at' => now()->subMinute(),
            ])->save();

            throw new \RuntimeException('Zoom token refresh temporarily failed. Please try again shortly.', 0, $exception);
        }

        return $this->storeUserToken(
            User::query()->findOrFail($token->user_id),
            array_merge($payload, ['refresh_token' => $payload['refresh_token'] ?? $refreshToken]),
            ['id' => $token->provider_user_id]
        );
    }

    public function verifyWebhookSignature(string $payload, string $signature, string $timestamp): void
    {
        $secret = $this->webhookSecretToken();

        if ($secret === null) {
            throw new \RuntimeException('Zoom webhook secret token is not configured.');
        }

        if ($signature === '' || $timestamp === '') {
            throw new \RuntimeException('Missing Zoom webhook signature headers.');
        }

        $expected = 'v0='.hash_hmac('sha256', sprintf('v0:%s:%s', $timestamp, $payload), $secret);

        if (! hash_equals($expected, $signature)) {
            throw new \RuntimeException('Invalid Zoom webhook signature.');
        }

        Log::debug('Zoom webhook signature verified.', [
            'timestamp' => $timestamp,
            'payload_bytes' => strlen($payload),
        ]);
    }

    public function webhookValidationToken(string $plainToken): string
    {
        $secret = $this->webhookSecretToken();

        if ($secret === null) {
            throw new \RuntimeException('Zoom webhook secret token is not configured.');
        }

        return hash_hmac('sha256', $plainToken, $secret);
    }

    private function meetingPayload(Booking $booking): array
    {
        $start = $booking->session_at ?: now()->utc();
        $mentorName = $booking->mentor?->user?->name ?? 'Mentor';
        $bookerName = $booking->booker?->name ?? 'Booker';

        return [
            'topic' => sprintf('%s with %s and %s', $booking->service?->service_name ?? 'Grads Paths Session', $bookerName, $mentorName),
            'type' => 2,
            'start_time' => $this->zoomDateTime($start),
            'duration' => max((int) $booking->duration_minutes, 1),
            'timezone' => $booking->session_timezone ?: config('app.timezone', 'UTC'),
            'agenda' => $this->agenda($booking),
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'waiting_room' => true,
                'mute_upon_entry' => false,
            ],
        ];
    }

    private function agenda(Booking $booking): string
    {
        $lines = [
            'Grads Paths booking confirmed.',
            'Booking ID: '.$booking->id,
            'Service: '.($booking->service?->service_name ?? 'Service'),
            'Session type: '.$this->sessionTypeLabel($booking->session_type),
        ];

        if ($booking->booker?->email) {
            $lines[] = 'Booker email: '.$booking->booker->email;
        }

        return implode("\n", $lines);
    }

    private function sessionTypeLabel(?string $sessionType): string
    {
        return match ($sessionType) {
            '1on3' => '1 on 3',
            '1on5' => '1 on 5',
            'office_hours' => 'Office Hours',
            default => '1 on 1',
        };
    }

    private function zoomDateTime(CarbonInterface $dateTime): string
    {
        return $dateTime->copy()->utc()->format('Y-m-d\TH:i:s\Z');
    }

    private function apiBase(): string
    {
        return rtrim((string) config('services.zoom.api_base', 'https://api.zoom.us/v2'), '/');
    }

    private function clientId(): ?string
    {
        $value = trim((string) config('services.zoom.client_id', ''));

        return $value === '' ? null : $value;
    }

    private function clientSecret(): ?string
    {
        $value = trim((string) config('services.zoom.client_secret', ''));

        return $value === '' ? null : $value;
    }

    private function redirectUri(): ?string
    {
        $value = trim((string) config('services.zoom.redirect_uri', ''));

        return $value === '' ? null : $value;
    }

    private function authorizeUrl(): string
    {
        return rtrim((string) config('services.zoom.authorize_url', 'https://zoom.us/oauth/authorize'), '/');
    }

    private function tokenUrl(): string
    {
        return rtrim((string) config('services.zoom.token_url', 'https://zoom.us/oauth/token'), '/');
    }

    private function webhookSecretToken(): ?string
    {
        $value = trim((string) config('services.zoom.webhook_secret_token', ''));

        return $value === '' ? null : $value;
    }

    private function tokenRequest(array $payload): array
    {
        $response = Http::asForm()
            ->withBasicAuth((string) $this->clientId(), (string) $this->clientSecret())
            ->acceptJson()
            ->post($this->tokenUrl(), $payload)
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new \RuntimeException('Zoom token response was malformed.');
        }

        return $response;
    }

    private function retrieveCurrentUserProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get($this->apiBase().'/users/me')
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new \RuntimeException('Zoom user profile response was malformed.');
        }

        return $response;
    }

    private function clearUserToken(User $user): void
    {
        OauthToken::query()
            ->where('user_id', $user->id)
            ->where('provider', 'zoom')
            ->update([
                'access_token' => '',
                'refresh_token' => '',
                'token_expires_at' => now()->subMinute(),
            ]);
    }

    private function storeUserToken(User $user, array $payload, array $profile): OauthToken
    {
        $providerUserId = trim((string) (Arr::get($profile, 'id') ?: Arr::get($profile, 'email')));
        $accessToken = trim((string) ($payload['access_token'] ?? ''));
        $refreshToken = trim((string) ($payload['refresh_token'] ?? ''));

        if ($providerUserId === '') {
            throw new \RuntimeException('Zoom did not return a user identifier.');
        }

        if ($accessToken === '') {
            throw new \RuntimeException('Zoom did not return an access token.');
        }

        return OauthToken::query()->updateOrCreate(
            [
                'provider' => 'zoom',
                'provider_user_id' => $providerUserId,
            ],
            [
                'user_id' => $user->id,
                'provider_user_id' => $providerUserId,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                // Zoom access tokens are short-lived; the refresh token is stored separately and reused until Zoom rotates or revokes it.
                'token_expires_at' => now()->addSeconds(max((int) ($payload['expires_in'] ?? 3600) - 60, 60)),
            ]
        );
    }

    private function refreshFailureRequiresReconnect(\Throwable $exception): bool
    {
        if (! $exception instanceof RequestException) {
            return false;
        }

        $response = $exception->response;
        $error = strtolower((string) data_get($response->json(), 'error'));

        if ($error === 'invalid_grant') {
            return true;
        }

        return $response->status() === 400 && str_contains(strtolower($response->body()), 'invalid_grant');
    }

    private function mentorUserForBooking(Booking $booking): User
    {
        $user = $booking->mentor?->user;

        if (! $user) {
            throw new \RuntimeException('The booking mentor user could not be resolved.');
        }

        return $user;
    }
}

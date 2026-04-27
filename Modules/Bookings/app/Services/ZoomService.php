<?php

namespace Modules\Bookings\app\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Bookings\app\Models\Booking;

class ZoomService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.zoom.enabled', false)
            && $this->accountId() !== null
            && $this->clientId() !== null
            && $this->clientSecret() !== null;
    }

    public function createMeeting(Booking $booking): array
    {
        $payload = $this->meetingPayload($booking);

        Log::info('Zoom meeting create request prepared.', [
            'booking_id' => $booking->id,
            'mentor_id' => $booking->mentor_id,
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

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->apiBase().'/users/me/meetings', $payload)
            ->throw();

        $meeting = $response->json();

        Log::info('Zoom meeting create response received.', [
            'booking_id' => $booking->id,
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

    public function cancelMeeting(string $meetingId): void
    {
        Log::info('Zoom meeting cancellation request prepared.', [
            'meeting_id' => $meetingId,
            'api_base' => $this->apiBase(),
        ]);

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->delete($this->apiBase().'/meetings/'.$meetingId)
            ->throw();

        Log::info('Zoom meeting cancellation response received.', [
            'meeting_id' => $meetingId,
            'status' => $response->status(),
        ]);
    }

    public function getMeeting(string $meetingId): array
    {
        Log::info('Zoom meeting retrieve request prepared.', [
            'meeting_id' => $meetingId,
            'api_base' => $this->apiBase(),
        ]);

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->get($this->apiBase().'/meetings/'.$meetingId)
            ->throw();

        $meeting = $response->json();

        Log::info('Zoom meeting retrieve response received.', [
            'meeting_id' => data_get($meeting, 'id') ?: $meetingId,
            'status' => $response->status(),
            'start_url_present' => filled(data_get($meeting, 'start_url')),
            'join_url_present' => filled(data_get($meeting, 'join_url')),
            'host_email_present' => filled(data_get($meeting, 'host_email')),
            'response_keys' => array_keys(is_array($meeting) ? $meeting : []),
        ]);

        return is_array($meeting) ? $meeting : [];
    }

    public function accessToken(): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Zoom is not fully configured.');
        }

        $cacheKey = 'zoom.server_to_server_access_token';
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            Log::debug('Using cached Zoom server-to-server access token.', [
                'cache_key' => $cacheKey,
            ]);

            return $cached;
        }

        Log::info('Requesting Zoom server-to-server access token.', [
            'account_id_present' => $this->accountId() !== null,
            'client_id_present' => $this->clientId() !== null,
        ]);

        $response = Http::asForm()
            ->withBasicAuth((string) $this->clientId(), (string) $this->clientSecret())
            ->acceptJson()
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $this->accountId(),
            ])
            ->throw()
            ->json();

        $token = trim((string) ($response['access_token'] ?? ''));

        if ($token === '') {
            throw new \RuntimeException('Zoom did not return an access token.');
        }

        $ttlSeconds = max(((int) ($response['expires_in'] ?? 3600)) - 60, 60);
        Cache::put($cacheKey, $token, now()->addSeconds($ttlSeconds));

        Log::info('Stored Zoom server-to-server access token in cache.', [
            'cache_key' => $cacheKey,
            'ttl_seconds' => $ttlSeconds,
            'scope_present' => filled($response['scope'] ?? null),
        ]);

        return $token;
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

    private function accountId(): ?string
    {
        $value = trim((string) config('services.zoom.account_id', ''));

        return $value === '' ? null : $value;
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

    private function webhookSecretToken(): ?string
    {
        $value = trim((string) config('services.zoom.webhook_secret_token', ''));

        return $value === '' ? null : $value;
    }
}

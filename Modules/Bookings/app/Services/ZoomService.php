<?php

namespace Modules\Bookings\app\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->apiBase().'/users/me/meetings', $this->meetingPayload($booking))
            ->throw();

        return $response->json();
    }

    public function cancelMeeting(string $meetingId): void
    {
        Http::withToken($this->accessToken())
            ->acceptJson()
            ->delete($this->apiBase().'/meetings/'.$meetingId)
            ->throw();
    }

    public function accessToken(): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Zoom is not fully configured.');
        }

        $cacheKey = 'zoom.server_to_server_access_token';
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

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

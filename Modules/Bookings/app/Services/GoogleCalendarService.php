<?php

namespace Modules\Bookings\app\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleCalendarService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.google_calendar.enabled', false);
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled()
            && $this->serviceAccountEmail() !== null
            && $this->privateKey() !== null
            && $this->calendarId() !== null;
    }

    public function createEvent(array $payload): array
    {
        $response = Http::withToken($this->accessToken())
            ->post($this->eventsEndpoint(), $payload);

        if ($response->failed()) {
            throw new RuntimeException('Google Calendar create event failed: '.$response->body());
        }

        return $response->json();
    }

    public function allowedConferenceSolutionTypes(): array
    {
        $response = Http::withToken($this->accessToken())
            ->get($this->calendarEndpoint());

        if ($response->failed()) {
            throw new RuntimeException('Google Calendar get calendar metadata failed: '.$response->body());
        }

        return collect($response->json('conferenceProperties.allowedConferenceSolutionTypes', []))
            ->filter(fn ($type) => is_string($type) && $type !== '')
            ->values()
            ->all();
    }

    public function cancelEvent(string $eventId): void
    {
        $response = Http::withToken($this->accessToken())
            ->delete($this->eventEndpoint($eventId));

        if ($response->failed() && $response->status() !== 404) {
            throw new RuntimeException('Google Calendar cancel event failed: '.$response->body());
        }
    }

    private function accessToken(): string
    {
        $issuedAt = time();
        $claims = [
            'iss' => $this->serviceAccountEmail(),
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'aud' => $this->tokenUri(),
            'exp' => $issuedAt + 3600,
            'iat' => $issuedAt,
        ];

        $jwt = $this->signJwt($claims);

        $response = Http::asForm()->post($this->tokenUri(), [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Google OAuth token request failed: '.$response->body());
        }

        $token = $response->json('access_token');

        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Google OAuth token response did not include an access token.');
        }

        return $token;
    }

    private function signJwt(array $claims): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];

        $signingInput = implode('.', $segments);
        $signature = '';

        $result = openssl_sign($signingInput, $signature, $this->privateKey(), OPENSSL_ALGO_SHA256);

        if (!$result) {
            throw new RuntimeException('Failed to sign Google service account JWT.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function eventsEndpoint(): string
    {
        return sprintf(
            '%s/calendars/%s/events?conferenceDataVersion=1&sendUpdates=all',
            $this->apiBase(),
            rawurlencode((string) $this->calendarId()),
        );
    }

    private function eventEndpoint(string $eventId): string
    {
        return sprintf(
            '%s/calendars/%s/events/%s?sendUpdates=all',
            $this->apiBase(),
            rawurlencode((string) $this->calendarId()),
            rawurlencode($eventId),
        );
    }

    private function calendarEndpoint(): string
    {
        return sprintf(
            '%s/calendars/%s?conferenceDataVersion=1',
            $this->apiBase(),
            rawurlencode((string) $this->calendarId()),
        );
    }

    private function apiBase(): string
    {
        return rtrim((string) config('services.google_calendar.api_base', 'https://www.googleapis.com/calendar/v3'), '/');
    }

    private function tokenUri(): string
    {
        return (string) config('services.google_calendar.token_uri', 'https://oauth2.googleapis.com/token');
    }

    private function calendarId(): ?string
    {
        $value = trim((string) config('services.google_calendar.calendar_id', ''));

        return $value === '' ? null : $value;
    }

    private function serviceAccountEmail(): ?string
    {
        $value = trim((string) config('services.google_calendar.service_account_email', ''));

        return $value === '' ? null : $value;
    }

    private function privateKey(): ?string
    {
        $value = trim((string) config('services.google_calendar.private_key', ''));

        if ($value === '') {
            return null;
        }

        return Str::replace('\n', "\n", $value);
    }
}

<?php

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

it('accepts signed zoom webhook validation requests and logs receipt', function () {
    Log::spy();

    config()->set('services.zoom.webhook_secret_token', 'zoom-webhook-secret');

    $payload = json_encode([
        'event' => 'endpoint.url_validation',
        'event_id' => 'zoom-validation-event-123',
        'payload' => [
            'plainToken' => 'zoom-plain-token',
        ],
    ]);
    $timestamp = (string) now()->timestamp;

    $response = $this->call(
        'POST',
        route('webhooks.zoom'),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ZM_REQUEST_TIMESTAMP' => $timestamp,
            'HTTP_X_ZM_SIGNATURE' => zoomWebhookSignature($payload, $timestamp, 'zoom-webhook-secret'),
        ],
        $payload
    );

    $response->assertOk()
        ->assertJson([
            'plainToken' => 'zoom-plain-token',
            'encryptedToken' => hash_hmac('sha256', 'zoom-plain-token', 'zoom-webhook-secret'),
        ]);

    Log::shouldHaveReceived('info')->with(
        'Zoom webhook received.',
        Mockery::on(fn (array $context): bool => $context['path'] === 'webhooks/zoom'
            && $context['event'] === 'endpoint.url_validation'
            && $context['event_id'] === 'zoom-validation-event-123'
            && $context['payload_decoded'] === true
            && $context['signature_present'] === true
            && $context['timestamp_present'] === true)
    );
});

function zoomWebhookSignature(string $payload, string $timestamp, string $secret): string
{
    return 'v0='.hash_hmac('sha256', sprintf('v0:%s:%s', $timestamp, $payload), $secret);
}

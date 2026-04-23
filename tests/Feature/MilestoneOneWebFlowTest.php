<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Payments\app\Jobs\ProcessStripeWebhookJob;

uses(RefreshDatabase::class);

it('renders auth entry pages from landing page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Grads Paths', false);

    $this->get('/register')
        ->assertOk()
        ->assertSee('Grads Paths', false);
});

it('redirects guests from protected milestone routes', function (string $method, string $uri) {
    $response = match ($method) {
        'GET' => $this->get($uri),
        'POST' => $this->post($uri),
        'PATCH' => $this->patch($uri),
        'DELETE' => $this->delete($uri),
        default => throw new InvalidArgumentException("Unsupported method: {$method}"),
    };

    $response->assertRedirect('/login');
})->with([
    ['GET', '/student/dashboard'],
    ['GET', '/student/mentors'],
    ['GET', '/student/institutions'],
    ['GET', '/student/bookings'],
    ['POST', '/student/bookings'],
    ['GET', '/student/store'],
    ['POST', '/student/store/purchase'],
    ['GET', '/student/feedback'],
    ['POST', '/student/feedback'],
    ['GET', '/student/support'],
    ['POST', '/student/support'],
]);

it('queues stripe webhook processing without requiring auth', function () {
    Queue::fake();

    $this->post('/webhooks/stripe', [
        'id' => 'evt_test_m1',
        'type' => 'checkout.session.completed',
    ])->assertStatus(202);

    Queue::assertPushed(ProcessStripeWebhookJob::class);
});

it('accepts zoom endpoint validation webhooks without requiring auth', function () {
    config([
        'services.zoom.webhook_secret_token' => 'zoom-secret-token',
    ]);

    $payload = [
        'event' => 'endpoint.url_validation',
        'payload' => [
            'plainToken' => 'plain-token-123',
        ],
    ];

    $raw = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $timestamp = (string) now()->timestamp;
    $signature = 'v0='.hash_hmac('sha256', sprintf('v0:%s:%s', $timestamp, $raw), 'zoom-secret-token');

    $this->withHeaders([
        'x-zm-request-timestamp' => $timestamp,
        'x-zm-signature' => $signature,
    ])->postJson('/webhooks/zoom', $payload)
        ->assertOk()
        ->assertJson([
            'plainToken' => 'plain-token-123',
            'encryptedToken' => hash_hmac('sha256', 'plain-token-123', 'zoom-secret-token'),
        ]);
});

it('stores verified zoom meeting lifecycle webhooks without requiring auth', function () {
    config([
        'services.zoom.webhook_secret_token' => 'zoom-secret-token',
    ]);

    $payload = [
        'event' => 'meeting.started',
        'event_id' => 'evt_zoom_123',
        'event_ts' => 1713780000000,
        'payload' => [
            'object' => [
                'id' => '85787873250',
                'start_time' => '2026-04-23T13:00:00Z',
            ],
        ],
    ];

    $raw = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $timestamp = (string) now()->timestamp;
    $signature = 'v0='.hash_hmac('sha256', sprintf('v0:%s:%s', $timestamp, $raw), 'zoom-secret-token');

    $this->withHeaders([
        'x-zm-request-timestamp' => $timestamp,
        'x-zm-signature' => $signature,
    ])->postJson('/webhooks/zoom', $payload)
        ->assertStatus(202);

    $this->assertDatabaseHas('booking_meeting_events', [
        'provider' => 'zoom',
        'provider_meeting_id' => '85787873250',
        'event_id' => 'evt_zoom_123',
        'event_type' => 'meeting.started',
        'is_verified' => 1,
    ]);
});

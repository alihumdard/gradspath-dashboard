<?php

use Illuminate\Support\Facades\Queue;
use Modules\Payments\app\Jobs\ProcessStripeWebhookJob;

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

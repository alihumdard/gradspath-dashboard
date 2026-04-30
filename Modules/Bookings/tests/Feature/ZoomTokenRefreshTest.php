<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\app\Models\OauthToken;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Mail\ZoomReconnectRequiredMail;
use Modules\Bookings\app\Services\ZoomService;
use Modules\Settings\app\Models\Mentor;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.zoom.enabled', true);
    config()->set('services.zoom.client_id', 'zoom-client-id');
    config()->set('services.zoom.client_secret', 'zoom-client-secret');
    config()->set('services.zoom.redirect_uri', 'https://gradspath.test/mentor/settings/zoom/callback');
    config()->set('services.zoom.token_url', 'https://zoom.us/oauth/token');
});

it('keeps zoom tokens when refresh temporarily fails', function () {
    $user = zoomRefreshUser();
    zoomRefreshToken($user);

    Http::fake([
        'https://zoom.us/oauth/token' => Http::response(['message' => 'Server unavailable'], 500),
    ]);

    try {
        app(ZoomService::class)->accessTokenForUser($user);
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Zoom token refresh temporarily failed. Please try again shortly.');

        $token = OauthToken::query()->where('provider', 'zoom')->firstOrFail();

        expect($token->access_token)->toBe('expired-access-token')
            ->and($token->refresh_token)->toBe('valid-refresh-token');

        return;
    }

    $this->fail('Expected Zoom token refresh to fail temporarily.');
});

it('clears zoom tokens when refresh token is invalid', function () {
    $user = zoomRefreshUser();
    zoomRefreshToken($user);

    Http::fake([
        'https://zoom.us/oauth/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    try {
        app(ZoomService::class)->accessTokenForUser($user);
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Zoom connection expired or was revoked. Please reconnect Zoom.');

        $token = OauthToken::query()->where('provider', 'zoom')->firstOrFail();

        expect($token->access_token)->toBe('')
            ->and($token->refresh_token)->toBe('');

        return;
    }

    $this->fail('Expected Zoom token refresh to require reconnect.');
});

it('scheduled mentor refresh stores rotated zoom refresh tokens', function () {
    Mail::fake();

    $user = zoomRefreshUser();
    zoomRefreshToken($user);
    zoomRefreshMentor($user);

    Http::fake([
        'https://zoom.us/oauth/token' => Http::response([
            'access_token' => 'fresh-access-token',
            'refresh_token' => 'rotated-refresh-token',
            'expires_in' => 3600,
        ], 200),
    ]);

    $this->artisan('zoom:refresh-mentor-tokens')
        ->assertSuccessful();

    $token = OauthToken::query()->where('provider', 'zoom')->firstOrFail();

    expect($token->access_token)->toBe('fresh-access-token')
        ->and($token->refresh_token)->toBe('rotated-refresh-token')
        ->and($token->token_expires_at?->isFuture())->toBeTrue();

    Mail::assertNothingSent();
});

it('scheduled mentor refresh keeps tokens on temporary zoom failure', function () {
    Mail::fake();

    $user = zoomRefreshUser();
    zoomRefreshToken($user);
    zoomRefreshMentor($user);

    Http::fake([
        'https://zoom.us/oauth/token' => Http::response(['message' => 'Server unavailable'], 500),
    ]);

    $this->artisan('zoom:refresh-mentor-tokens')
        ->assertSuccessful();

    $token = OauthToken::query()->where('provider', 'zoom')->firstOrFail();

    expect($token->access_token)->toBe('expired-access-token')
        ->and($token->refresh_token)->toBe('valid-refresh-token');

    Mail::assertNothingSent();
});

it('scheduled mentor refresh clears invalid zoom tokens and sends reconnect email', function () {
    Mail::fake();

    $user = zoomRefreshUser();
    zoomRefreshToken($user);
    zoomRefreshMentor($user);

    Http::fake([
        'https://zoom.us/oauth/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    $this->artisan('zoom:refresh-mentor-tokens')
        ->assertSuccessful();

    $token = OauthToken::query()->where('provider', 'zoom')->firstOrFail();

    expect($token->access_token)->toBe('')
        ->and($token->refresh_token)->toBe('');

    Mail::assertSent(ZoomReconnectRequiredMail::class, fn (ZoomReconnectRequiredMail $mail) => $mail->hasTo($user->email));
});

function zoomRefreshUser(): User
{
    $created = \App\Models\User::factory()->create([
        'email' => fake()->unique()->safeEmail(),
        'is_active' => true,
    ]);

    return User::query()->findOrFail($created->id);
}

function zoomRefreshToken(User $user): OauthToken
{
    return OauthToken::query()->create([
        'user_id' => $user->id,
        'provider' => 'zoom',
        'provider_user_id' => 'zoom-user-123',
        'access_token' => 'expired-access-token',
        'refresh_token' => 'valid-refresh-token',
        'token_expires_at' => now()->subMinute(),
    ]);
}

function zoomRefreshMentor(User $user): Mentor
{
    return Mentor::query()->create([
        'user_id' => $user->id,
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'grad_school_display' => 'Harvard',
        'status' => 'active',
    ]);
}

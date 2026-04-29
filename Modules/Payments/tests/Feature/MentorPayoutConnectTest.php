<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Payments\app\Services\StripeWebhookService;
use Modules\Settings\app\Models\Mentor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::findOrCreate('mentor', 'web');
    config()->set('services.stripe.secret_key', 'sk_test_fake');
    config()->set('services.stripe.api_base', 'https://api.stripe.com/v1');
});

function createMentorUserForPayouts(): User
{
    $user = User::factory()->create([
        'email' => Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user->assignRole('mentor');

    return $user;
}

it('creates a connected account and redirects mentors to Stripe onboarding', function () {
    Http::fake([
        'https://api.stripe.com/v1/accounts' => Http::response([
            'id' => 'acct_new_123',
        ], 200),
        'https://api.stripe.com/v1/account_links' => Http::response([
            'url' => 'https://connect.stripe.test/onboarding/acct_new_123',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $mentorUser = createMentorUserForPayouts();
    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    $response = $this->withoutMiddleware()
        ->actingAs($mentorUser)
        ->get(route('mentor.payouts.connect'));

    $response->assertRedirect('https://connect.stripe.test/onboarding/acct_new_123');

    expect($mentorUser->fresh()->mentor?->stripe_account_id)->toBe('acct_new_123');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.stripe.com/v1/accounts'
            && str_contains($request->body(), 'type=express')
            && str_contains($request->body(), 'capabilities%5Btransfers%5D%5Brequested%5D=true');
    });

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.stripe.com/v1/account_links'
            && str_contains($request->body(), 'type=account_onboarding')
            && str_contains($request->body(), 'collection_options%5Bfields%5D=eventually_due');
    });
});

it('lets payout-enabled mentors open a hosted Stripe update flow', function () {
    Http::fake([
        'https://api.stripe.com/v1/account_links' => Http::response([
            'url' => 'https://connect.stripe.test/update/acct_existing_123',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $mentorUser = createMentorUserForPayouts();
    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
        'stripe_account_id' => 'acct_existing_123',
        'stripe_onboarding_complete' => true,
        'payouts_enabled' => true,
    ]);

    $response = $this->withoutMiddleware()
        ->actingAs($mentorUser)
        ->get(route('mentor.payouts.connect'));

    $response->assertRedirect('https://connect.stripe.test/update/acct_existing_123');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.stripe.com/v1/account_links'
            && str_contains($request->body(), 'account=acct_existing_123')
            && str_contains($request->body(), 'type=account_update');
    });
});

it('marks mentor payouts as enabled when stripe sends account updates', function () {
    $mentorUser = createMentorUserForPayouts();
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
        'stripe_account_id' => 'acct_sync_123',
        'stripe_onboarding_complete' => false,
        'payouts_enabled' => false,
    ]);

    app(StripeWebhookService::class)->process([
        'id' => 'evt_account_updated_123',
        'type' => 'account.updated',
        'data' => [
            'object' => [
                'id' => 'acct_sync_123',
                'details_submitted' => true,
                'payouts_enabled' => true,
                'requirements' => [
                    'currently_due' => [],
                    'eventually_due' => [],
                ],
            ],
        ],
    ]);

    expect($mentor->fresh()->payouts_enabled)->toBeTrue();
    expect($mentor->fresh()->stripe_onboarding_complete)->toBeTrue();

    $this->assertDatabaseHas('stripe_webhooks', [
        'event_id' => 'evt_account_updated_123',
        'event_type' => 'account.updated',
        'processed' => true,
    ]);
});

it('syncs mentor payout status when stripe sends an account link returned event', function () {
    Http::fake([
        'https://api.stripe.com/v1/accounts/acct_link_returned_123' => Http::response([
            'id' => 'acct_link_returned_123',
            'details_submitted' => true,
            'payouts_enabled' => true,
            'requirements' => [
                'currently_due' => [],
                'eventually_due' => [],
            ],
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $mentorUser = createMentorUserForPayouts();
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
        'stripe_account_id' => 'acct_link_returned_123',
        'stripe_onboarding_complete' => false,
        'payouts_enabled' => false,
    ]);

    app(StripeWebhookService::class)->process([
        'id' => 'evt_account_link_returned_123',
        'type' => 'v2.core.account_link.returned',
        'data' => [
            'account_id' => 'acct_link_returned_123',
            'configurations' => ['recipient', 'merchant'],
            'use_case' => 'account_onboarding',
        ],
    ]);

    expect($mentor->fresh()->payouts_enabled)->toBeTrue();
    expect($mentor->fresh()->stripe_onboarding_complete)->toBeTrue();

    $this->assertDatabaseHas('stripe_webhooks', [
        'event_id' => 'evt_account_link_returned_123',
        'event_type' => 'v2.core.account_link.returned',
        'processed' => true,
    ]);
});

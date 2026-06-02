<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Payments\app\Models\UserCredit;
use Modules\Settings\app\Models\Mentor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
    Role::findOrCreate('admin', 'web');

    config()->set('services.stripe.secret_key', 'sk_test_fake');
    config()->set('services.stripe.api_base', 'https://api.stripe.com/v1');
});

function createStoreMentorUser(): User
{
    $user = User::factory()->create([
        'email' => Str::uuid().'@example.com',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $user->assignRole('mentor');

    Mentor::query()->create([
        'user_id' => $user->id,
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
    ]);

    return $user;
}

it('renders the credit store inside the mentor portal shell', function () {
    $mentor = createStoreMentorUser();

    UserCredit::query()->create([
        'user_id' => $mentor->id,
        'balance' => 3,
    ]);

    $this->actingAs($mentor)
        ->get(route('mentor.store'))
        ->assertOk()
        ->assertSee('data-balance-url="'.route('mentor.credits.balance').'"', false)
        ->assertSee('Credits: <strong id="portalCreditsValue">3</strong>', false)
        ->assertSee('href="'.route('mentor.store').'"', false)
        ->assertSee('checkoutUrl', false)
        ->assertSee('mentor\/store\/checkout', false);
});

it('returns the mentor credit balance', function () {
    $mentor = createStoreMentorUser();

    UserCredit::query()->create([
        'user_id' => $mentor->id,
        'balance' => 7,
    ]);

    $this->actingAs($mentor)
        ->getJson(route('mentor.credits.balance'))
        ->assertOk()
        ->assertJson([
            'balance' => 7,
        ]);
});

it('starts mentor credit checkout with mentor portal redirects', function () {
    $mentor = createStoreMentorUser();
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    Http::fake([
        'https://api.stripe.com/v1/checkout/sessions' => Http::response([
            'id' => 'cs_test_mentor_credits',
            'url' => 'https://checkout.stripe.test/session',
        ]),
    ]);

    $this->actingAs($mentor)
        ->postJson(route('mentor.store.checkout'), [
            'credits' => (int) config('payments.office_hours.credit_pack_credits', 5),
            'office_hours_program' => 'mba',
        ])
        ->assertOk()
        ->assertJson([
            'checkout_url' => 'https://checkout.stripe.test/session',
            'session_id' => 'cs_test_mentor_credits',
        ]);

    Http::assertSent(function ($request) {
        $payload = $request->data();

        return $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
            && str_contains((string) ($payload['success_url'] ?? ''), '/mentor/store/success')
            && str_contains((string) ($payload['cancel_url'] ?? ''), '/mentor/store')
            && (string) data_get($payload, 'metadata.portal_context') === 'mentor';
    });
});

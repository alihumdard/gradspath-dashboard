<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\OauthToken;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::findOrCreate('mentor', 'web');
});

function createAvailabilityMentorUser(): User
{
    $created = \App\Models\User::factory()->create([
        'email' => Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user = User::query()->findOrFail($created->id);
    $user->assignRole('mentor');

    return $user;
}

function createAvailabilityMentorContext(): array
{
    $mentorUser = createAvailabilityMentorUser();
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'grad_school_display' => 'Wharton',
        'status' => 'active',
    ]);

    $service = ServiceConfig::query()->create([
        'service_name' => 'Program Insights',
        'service_slug' => 'program-insights',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 120,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 0,
    ]);

    $mentor->services()->attach($service->id, [
        'is_active' => true,
        'sort_order' => 0,
    ]);

    return compact('mentorUser', 'mentor', 'service');
}

function availabilityPayload(ServiceConfig $service, array $overrides = []): array
{
    $date = now()->addWeek()->toDateString();

    return array_replace_recursive([
        'date_slots_payload' => json_encode([[
            'date' => $date,
            'enabled' => true,
            'slots' => [[
                'start_time' => '14:00',
                'end_time' => '15:00',
                'service_config_id' => $service->id,
                'session_type' => '1on1',
            ]],
        ]], JSON_THROW_ON_ERROR),
        'office_hours' => [
            'enabled' => false,
        ],
    ], $overrides);
}

function configureZoom(): void
{
    config([
        'services.zoom.enabled' => true,
        'services.zoom.client_id' => 'zoom-client-id',
        'services.zoom.client_secret' => 'zoom-client-secret',
        'services.zoom.redirect_uri' => 'https://gradspath.test/mentor/settings/zoom/callback',
        'services.zoom.api_base' => 'https://api.zoom.us/v2',
        'services.zoom.token_url' => 'https://zoom.us/oauth/token',
    ]);
}

it('allows mentors to publish regular availability when zoom is connected and usable', function () {
    ['mentorUser' => $mentorUser, 'mentor' => $mentor, 'service' => $service] = createAvailabilityMentorContext();

    configureZoom();

    OauthToken::query()->create([
        'user_id' => $mentorUser->id,
        'provider' => 'zoom',
        'provider_user_id' => 'zoom-user-123',
        'access_token' => 'zoom-access-token',
        'refresh_token' => 'zoom-refresh-token',
        'token_expires_at' => now()->addHour(),
    ]);

    Http::fake([
        'https://api.zoom.us/v2/users/me' => Http::response([
            'id' => 'zoom-user-123',
            'email' => $mentorUser->email,
        ], 200),
    ]);

    $this->actingAs($mentorUser)
        ->patchJson(route('mentor.availability.update'), availabilityPayload($service))
        ->assertOk()
        ->assertJsonPath('message', 'Mentor availability updated successfully.');

    expect(MentorAvailabilitySlot::query()->where('mentor_id', $mentor->id)->count())->toBe(1);
});

it('blocks regular availability when the mentor has not connected zoom', function () {
    ['mentorUser' => $mentorUser, 'mentor' => $mentor, 'service' => $service] = createAvailabilityMentorContext();

    configureZoom();

    $this->actingAs($mentorUser)
        ->patchJson(route('mentor.availability.update'), availabilityPayload($service))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['date_slots']);

    expect(MentorAvailabilitySlot::query()->where('mentor_id', $mentor->id)->count())->toBe(0);
});

it('blocks regular availability when the mentor zoom connection is expired or revoked', function () {
    ['mentorUser' => $mentorUser, 'service' => $service] = createAvailabilityMentorContext();

    configureZoom();

    OauthToken::query()->create([
        'user_id' => $mentorUser->id,
        'provider' => 'zoom',
        'provider_user_id' => 'zoom-user-123',
        'access_token' => 'expired-access-token',
        'refresh_token' => 'valid-refresh-token',
        'token_expires_at' => now()->subMinute(),
    ]);

    Http::fake([
        'https://zoom.us/oauth/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    $this->actingAs($mentorUser)
        ->patchJson(route('mentor.availability.update'), availabilityPayload($service))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['date_slots']);

    $token = OauthToken::query()->where('provider', 'zoom')->firstOrFail();

    expect($token->access_token)->toBe('')
        ->and($token->refresh_token)->toBe('');
});

it('keeps office-hours-only availability updates working without zoom', function () {
    ['mentorUser' => $mentorUser, 'mentor' => $mentor, 'service' => $service] = createAvailabilityMentorContext();

    $this->actingAs($mentorUser)
        ->patchJson(route('mentor.availability.update'), availabilityPayload($service, [
            'date_slots_payload' => json_encode([], JSON_THROW_ON_ERROR),
            'office_hours' => [
                'enabled' => true,
                'service_config_id' => $service->id,
                'day_of_week' => 'mon',
                'start_time' => '14:00',
                'frequency' => 'weekly',
            ],
        ]))
        ->assertOk()
        ->assertJsonPath('message', 'Mentor availability updated successfully.');

    expect($mentor->fresh()->officeHourSchedules()->where('is_active', true)->count())->toBe(1);
});

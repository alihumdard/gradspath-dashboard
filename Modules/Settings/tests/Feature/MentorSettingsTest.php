<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\OauthToken;
use Modules\Auth\app\Models\User;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Models\UserSetting;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
    Role::findOrCreate('admin', 'web');
});

function createSettingsUser(string $role = 'mentor'): User
{
    $created = \App\Models\User::factory()->create([
        'email' => Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user = User::query()->findOrFail($created->id);
    $user->assignRole($role);

    return $user;
}

it('renders mentor settings with saved profile data', function () {
    $mentorUser = createSettingsUser('mentor');
    $university = University::query()->create([
        'name' => 'University of Pennsylvania',
        'display_name' => 'Wharton',
        'country' => 'US',
        'is_active' => true,
    ]);
    UniversityProgram::query()->create([
        'university_id' => $university->id,
        'program_name' => 'MBA (Wharton School)',
        'program_type' => 'mba',
        'tier' => 'elite',
        'is_active' => true,
    ]);

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $university->id,
        'mentor_type' => 'graduate',
        'title' => 'MBA Mentor',
        'program_type' => 'mba',
        'grad_school_display' => 'Wharton',
        'bio' => 'Focused on admissions strategy.',
        'office_hours_schedule' => 'Fridays at 3 PM EST',
        'status' => 'active',
    ]);

    $service = ServiceConfig::query()->create([
        'service_name' => 'Program Insights',
        'service_slug' => 'program-insights',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 100,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 0,
    ]);

    $mentor->services()->attach($service->id, ['sort_order' => 0]);

    $response = $this->withoutMiddleware()->actingAs($mentorUser)->get(route('mentor.settings.index'));

    $response->assertOk();
    $response->assertSee('Mentor Settings');
    $response->assertSee($mentorUser->email);
    $response->assertSee('MBA Mentor');
    $response->assertSee('Academic Profile');
    $response->assertSee('Wharton');
    $response->assertSee('MBA (Wharton School)');
    $response->assertSee('Focused on admissions strategy.');
    $response->assertSee('Timezone');
    $response->assertSee('mentor-status-badge--active', false);
    $response->assertSee('Active');
    $response->assertDontSee('Featured Mentor');
    $response->assertDontSee('id="officeHours"', false);
});

it('renders paused mentor settings as read only with a status badge', function () {
    $mentorUser = createSettingsUser('mentor');

    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'title' => 'Paused Mentor',
        'status' => 'paused',
    ]);

    $response = $this->actingAs($mentorUser)->get(route('mentor.settings.index'));

    $response->assertOk();
    $response->assertSee('mentor-status-badge--paused', false);
    $response->assertSee('Paused');
    $response->assertSee('Your mentor profile is currently restricted.');
    $response->assertSee('class="mentor-settings-fieldset" disabled', false);
    $response->assertSee('aria-disabled="true"', false);
});

it('shows a reconnect warning when mentor zoom refresh token is missing', function () {
    config()->set('services.zoom.enabled', true);
    config()->set('services.zoom.client_id', 'zoom-client-id');
    config()->set('services.zoom.client_secret', 'zoom-client-secret');
    config()->set('services.zoom.redirect_uri', 'https://gradspath.test/mentor/settings/zoom/callback');

    $mentorUser = createSettingsUser('mentor');

    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'title' => 'Zoom Mentor',
        'status' => 'active',
    ]);

    OauthToken::query()->create([
        'user_id' => $mentorUser->id,
        'provider' => 'zoom',
        'provider_user_id' => 'zoom-user-'.$mentorUser->id,
        'access_token' => '',
        'refresh_token' => '',
        'token_expires_at' => now()->subMinute(),
    ]);

    $response = $this->actingAs($mentorUser)->get(route('mentor.settings.index'));

    $response->assertOk();
    $response->assertSee('Reconnect required');
    $response->assertSee('Your Zoom connection needs to be reconnected before students can book Zoom sessions.');
});

it('blocks paused mentors from updating settings', function () {
    $mentorUser = createSettingsUser('mentor');

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'title' => 'Original Title',
        'status' => 'paused',
    ]);

    $this->actingAs($mentorUser)
        ->from(route('mentor.settings.index'))
        ->patch(route('mentor.settings.update'), [
            'name' => 'Blocked Mentor',
            'email' => 'blocked@example.com',
            'mentor_type' => 'graduate',
            'title' => 'Updated Title',
            'timezone' => 'Asia/Karachi',
        ])
        ->assertRedirect(route('mentor.settings.index'))
        ->assertSessionHas('error');

    expect($mentor->fresh()->title)->toBe('Original Title');
    expect($mentorUser->fresh()->email)->not->toBe('blocked@example.com');
});

it('blocks paused mentors from operational mentor routes', function () {
    $mentorUser = createSettingsUser('mentor');

    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'paused',
    ]);

    foreach ([
        route('mentor.dashboard'),
        route('mentor.bookings.index'),
        route('mentor.availability.index'),
        route('mentor.office-hours'),
        route('mentor.notes'),
        route('mentor.institutions.index'),
        route('mentor.payouts.connect'),
        route('mentor.payouts.status'),
    ] as $url) {
        $this->actingAs($mentorUser)
            ->get($url)
            ->assertRedirect(route('mentor.settings.index'))
            ->assertSessionHas('error');
    }
});

it('keeps inactive mentor users blocked by the active account middleware', function () {
    $mentorUser = createSettingsUser('mentor');
    $mentorUser->forceFill(['is_active' => false])->save();

    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    $this->actingAs($mentorUser)
        ->get(route('mentor.settings.index'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('updates mentor settings', function () {
    $mentorUser = createSettingsUser('mentor');
    $university = University::query()->create([
        'name' => 'Yale University',
        'display_name' => 'Yale Law',
        'country' => 'US',
        'is_active' => true,
    ]);
    $program = UniversityProgram::query()->create([
        'university_id' => $university->id,
        'program_name' => 'JD (Yale School of Law)',
        'program_type' => 'law',
        'tier' => 'elite',
        'is_active' => true,
    ]);

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $university->id,
        'mentor_type' => 'graduate',
        'office_hours_schedule' => 'Existing office hours schedule',
        'is_featured' => true,
        'status' => 'active',
    ]);

    $response = $this->withoutMiddleware()->actingAs($mentorUser)->patch(route('mentor.settings.update'), [
        'name' => 'Mentor Example',
        'email' => 'mentor@example.com',
        'mentor_type' => 'graduate',
        'title' => 'Law Mentor',
        'university_id' => $university->id,
        'university_program_id' => $program->id,
        'grad_school_display' => 'Yale Law',
        'bio' => 'Helping students sharpen essays and interviews.',
        'description' => 'Longer mentoring profile copy for expanded views.',
        'edu_email' => 'mentor@yale.edu',
        'calendly_link' => 'https://calendly.com/mentor-example',
        'timezone' => 'Asia/Karachi',
    ]);

    $response->assertRedirect(route('mentor.settings.index'));

    expect($mentorUser->fresh()->name)->toBe('Mentor Example');
    expect($mentorUser->fresh()->email)->toBe('mentor@example.com');

    $mentor->refresh();

    expect($mentor->title)->toBe('Law Mentor');
    expect($mentor->university_id)->toBe($university->id);
    expect($mentor->university_program_id)->toBe($program->id);
    expect($mentor->program_type)->toBe('law');
    expect($mentor->grad_school_display)->toBe('Yale Law');
    expect($mentor->bio)->toBe('Helping students sharpen essays and interviews.');
    expect($mentor->office_hours_schedule)->toBe('Existing office hours schedule');
    expect($mentor->edu_email)->toBe('mentor@yale.edu');
    expect($mentor->is_featured)->toBeTrue();
    expect($mentorUser->fresh()->setting?->timezone)->toBe('Asia/Karachi');
});

it('rejects mentor program selections outside the mentor university', function () {
    $mentorUser = createSettingsUser('mentor');
    $mentorUniversity = University::query()->create([
        'name' => 'University of Lahore',
        'country' => 'PK',
        'is_active' => true,
    ]);
    $otherUniversity = University::query()->create([
        'name' => 'Yale University',
        'country' => 'US',
        'is_active' => true,
    ]);
    $otherProgram = UniversityProgram::query()->create([
        'university_id' => $otherUniversity->id,
        'program_name' => 'JD (Yale School of Law)',
        'program_type' => 'law',
        'tier' => 'elite',
        'is_active' => true,
    ]);

    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $mentorUniversity->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    $response = $this->actingAs($mentorUser)->from(route('mentor.settings.index'))->patch(route('mentor.settings.update'), [
        'name' => 'Mentor Example',
        'email' => 'mentor@example.com',
        'mentor_type' => 'graduate',
        'university_id' => $mentorUniversity->id,
        'university_program_id' => $otherProgram->id,
        'edu_email' => 'mentor@uol.edu',
    ]);

    $response->assertRedirect(route('mentor.settings.index'));
    $response->assertSessionHasErrors('university_program_id');
});

it('updates mentor university and selected related program from settings', function () {
    $mentorUser = createSettingsUser('mentor');
    $oldUniversity = University::query()->create([
        'name' => 'Yale University',
        'country' => 'US',
        'is_active' => true,
    ]);
    $newUniversity = University::query()->create([
        'name' => 'University of Lahore',
        'display_name' => 'UOL',
        'country' => 'PK',
        'is_active' => true,
    ]);
    $newProgram = UniversityProgram::query()->create([
        'university_id' => $newUniversity->id,
        'program_name' => 'MBA',
        'program_type' => 'mba',
        'tier' => 'regional',
        'is_active' => true,
    ]);

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $oldUniversity->id,
        'mentor_type' => 'graduate',
        'program_type' => 'law',
        'grad_school_display' => 'Yale',
        'status' => 'active',
    ]);

    $response = $this->actingAs($mentorUser)->patch(route('mentor.settings.update'), [
        'name' => 'Mentor Example',
        'email' => 'mentor@example.com',
        'mentor_type' => 'graduate',
        'university_id' => $newUniversity->id,
        'university_program_id' => $newProgram->id,
        'grad_school_display' => '',
        'edu_email' => 'mentor@uol.edu',
    ]);

    $response->assertRedirect(route('mentor.settings.index'));

    $mentor->refresh();

    expect($mentor->university_id)->toBe($newUniversity->id);
    expect($mentor->university_program_id)->toBe($newProgram->id);
    expect($mentor->program_type)->toBe('mba');
    expect($mentor->grad_school_display)->toBe('UOL');
});

it('clears the old selected program when mentor changes university without selecting a program', function () {
    $mentorUser = createSettingsUser('mentor');
    $oldUniversity = University::query()->create([
        'name' => 'Yale University',
        'country' => 'US',
        'is_active' => true,
    ]);
    $oldProgram = UniversityProgram::query()->create([
        'university_id' => $oldUniversity->id,
        'program_name' => 'JD',
        'program_type' => 'law',
        'tier' => 'elite',
        'is_active' => true,
    ]);
    $newUniversity = University::query()->create([
        'name' => 'University of Lahore',
        'country' => 'PK',
        'is_active' => true,
    ]);

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $oldUniversity->id,
        'university_program_id' => $oldProgram->id,
        'mentor_type' => 'graduate',
        'program_type' => 'law',
        'status' => 'active',
    ]);

    $response = $this->actingAs($mentorUser)->patch(route('mentor.settings.update'), [
        'name' => 'Mentor Example',
        'email' => 'mentor@example.com',
        'mentor_type' => 'graduate',
        'university_id' => $newUniversity->id,
        'university_program_id' => '',
        'edu_email' => 'mentor@uol.edu',
    ]);

    $response->assertRedirect(route('mentor.settings.index'));

    $mentor->refresh();

    expect($mentor->university_id)->toBe($newUniversity->id);
    expect($mentor->university_program_id)->toBeNull();
    expect($mentor->program_type)->toBeNull();
});

it('rejects inactive university and inactive program selections', function () {
    $mentorUser = createSettingsUser('mentor');
    $activeUniversity = University::query()->create([
        'name' => 'University of Lahore',
        'country' => 'PK',
        'is_active' => true,
    ]);
    $inactiveUniversity = University::query()->create([
        'name' => 'Inactive University',
        'country' => 'PK',
        'is_active' => false,
    ]);
    $inactiveProgram = UniversityProgram::query()->create([
        'university_id' => $activeUniversity->id,
        'program_name' => 'Inactive MBA',
        'program_type' => 'mba',
        'tier' => 'regional',
        'is_active' => false,
    ]);

    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $activeUniversity->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    $this->actingAs($mentorUser)->from(route('mentor.settings.index'))->patch(route('mentor.settings.update'), [
        'name' => 'Mentor Example',
        'email' => 'mentor@example.com',
        'mentor_type' => 'graduate',
        'university_id' => $inactiveUniversity->id,
        'edu_email' => 'mentor@uol.edu',
    ])->assertSessionHasErrors('university_id');

    $this->actingAs($mentorUser)->from(route('mentor.settings.index'))->patch(route('mentor.settings.update'), [
        'name' => 'Mentor Example',
        'email' => 'mentor@example.com',
        'mentor_type' => 'graduate',
        'university_id' => $activeUniversity->id,
        'university_program_id' => $inactiveProgram->id,
        'edu_email' => 'mentor@uol.edu',
    ])->assertSessionHasErrors('university_program_id');
});

it('returns only active programs for active universities from the mentor settings endpoint', function () {
    $mentorUser = createSettingsUser('mentor');
    $university = University::query()->create([
        'name' => 'University of Lahore',
        'country' => 'PK',
        'is_active' => true,
    ]);
    $inactiveUniversity = University::query()->create([
        'name' => 'Inactive University',
        'country' => 'PK',
        'is_active' => false,
    ]);
    $activeProgram = UniversityProgram::query()->create([
        'university_id' => $university->id,
        'program_name' => 'MBA',
        'program_type' => 'mba',
        'tier' => 'regional',
        'is_active' => true,
    ]);
    UniversityProgram::query()->create([
        'university_id' => $university->id,
        'program_name' => 'Inactive Law',
        'program_type' => 'law',
        'tier' => 'regional',
        'is_active' => false,
    ]);
    UniversityProgram::query()->create([
        'university_id' => $inactiveUniversity->id,
        'program_name' => 'Hidden MBA',
        'program_type' => 'mba',
        'tier' => 'regional',
        'is_active' => true,
    ]);

    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $university->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    $this->actingAs($mentorUser)
        ->getJson(route('mentor.settings.university-programs', ['university_id' => $university->id]))
        ->assertOk()
        ->assertJsonPath('data.0.id', $activeProgram->id)
        ->assertJsonCount(1, 'data');

    $this->actingAs($mentorUser)
        ->getJson(route('mentor.settings.university-programs', ['university_id' => $inactiveUniversity->id]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('requires a .edu email for graduate mentors', function () {
    $mentorUser = createSettingsUser('mentor');

    $response = $this->withoutMiddleware(\App\Http\Middleware\EnsureMentorApproved::class)
        ->actingAs($mentorUser)
        ->from(route('mentor.settings.index'))
        ->patch(route('mentor.settings.update'), [
        'name' => 'Mentor Example',
        'email' => 'mentor@gmail.com',
        'mentor_type' => 'graduate',
        'edu_email' => 'mentor@gmail.com',
    ]);

    $response->assertRedirect(route('mentor.settings.index'));
    $response->assertSessionHasErrors('edu_email');

    expect(Mentor::query()->where('user_id', $mentorUser->id)->exists())->toBeFalse();
});

it('auto-saves a detected supported timezone when the user has no saved timezone', function () {
    $mentorUser = createSettingsUser('mentor');
    UserSetting::query()->create([
        'user_id' => $mentorUser->id,
        'theme' => 'light',
        'email_notifications' => true,
        'sms_notifications' => false,
        'timezone' => null,
    ]);

    $this->withoutMiddleware()
        ->actingAs($mentorUser)
        ->postJson(route('settings.timezone.store'), [
            'timezone' => 'Asia/Karachi',
        ])
        ->assertOk()
        ->assertJsonPath('timezone', 'Asia/Karachi');

    expect($mentorUser->fresh()->setting?->timezone)->toBe('Asia/Karachi');
});

it('does not overwrite a saved supported timezone from the detection endpoint', function () {
    $mentorUser = createSettingsUser('mentor');
    UserSetting::query()->create([
        'user_id' => $mentorUser->id,
        'theme' => 'light',
        'email_notifications' => true,
        'sms_notifications' => false,
        'timezone' => 'Asia/Karachi',
    ]);

    $this->withoutMiddleware()
        ->actingAs($mentorUser)
        ->postJson(route('settings.timezone.store'), [
            'timezone' => 'Asia/Karachi',
        ])
        ->assertOk()
        ->assertJsonPath('timezone', 'Asia/Karachi');

    expect($mentorUser->fresh()->setting?->timezone)->toBe('Asia/Karachi');
});

it('rejects unsupported detected timezones', function () {
    $mentorUser = createSettingsUser('mentor');

    $this->withoutMiddleware()
        ->actingAs($mentorUser)
        ->postJson(route('settings.timezone.store'), [
            'timezone' => 'Asia/Dubai',
        ])
        ->assertStatus(422);

    expect($mentorUser->fresh()->setting?->timezone)->toBeNull();
});

it('starts the mentor zoom oauth flow from settings', function () {
    $mentorUser = createSettingsUser('mentor');
    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    config([
        'services.zoom.enabled' => true,
        'services.zoom.client_id' => 'zoom-client-id',
        'services.zoom.client_secret' => 'zoom-client-secret',
        'services.zoom.redirect_uri' => 'https://gradspath.test/mentor/settings/zoom/callback',
    ]);

    $response = $this->actingAs($mentorUser)->get(route('mentor.settings.zoom.connect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('https://zoom.us/oauth/authorize');
    expect(session('mentor_zoom_oauth_state'))->not->toBeEmpty();
});

it('stores the mentor zoom token after a successful oauth callback', function () {
    $mentorUser = createSettingsUser('mentor');
    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    config([
        'services.zoom.enabled' => true,
        'services.zoom.client_id' => 'zoom-client-id',
        'services.zoom.client_secret' => 'zoom-client-secret',
        'services.zoom.redirect_uri' => 'https://gradspath.test/mentor/settings/zoom/callback',
        'services.zoom.api_base' => 'https://api.zoom.us/v2',
    ]);

    Http::fake([
        'https://zoom.us/oauth/token' => Http::response([
            'access_token' => 'zoom-access-token',
            'refresh_token' => 'zoom-refresh-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'https://api.zoom.us/v2/users/me' => Http::response([
            'id' => 'zoom-user-123',
            'email' => 'mentor.zoom@example.com',
        ], 200),
    ]);

    $this->actingAs($mentorUser)
        ->withSession(['mentor_zoom_oauth_state' => 'zoom-state-123'])
        ->get(route('mentor.settings.zoom.callback', [
            'code' => 'zoom-auth-code',
            'state' => 'zoom-state-123',
        ]))
        ->assertRedirect(route('mentor.settings.index'));

    $this->assertDatabaseHas('oauth_tokens', [
        'user_id' => $mentorUser->id,
        'provider' => 'zoom',
        'provider_user_id' => 'zoom-user-123',
    ]);
});

it('moves an existing zoom token to the reconnecting mentor instead of duplicating it', function () {
    $oldMentorUser = createSettingsUser('mentor');
    $newMentorUser = createSettingsUser('mentor');
    Mentor::query()->create([
        'user_id' => $newMentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    OauthToken::query()->create([
        'user_id' => $oldMentorUser->id,
        'provider' => 'zoom',
        'provider_user_id' => 'zoom-user-123',
        'access_token' => 'old-zoom-access-token',
        'refresh_token' => 'old-zoom-refresh-token',
        'token_expires_at' => now()->addHour(),
    ]);

    config([
        'services.zoom.enabled' => true,
        'services.zoom.client_id' => 'zoom-client-id',
        'services.zoom.client_secret' => 'zoom-client-secret',
        'services.zoom.redirect_uri' => 'https://gradspath.test/mentor/settings/zoom/callback',
        'services.zoom.api_base' => 'https://api.zoom.us/v2',
    ]);

    Http::fake([
        'https://zoom.us/oauth/token' => Http::response([
            'access_token' => 'new-zoom-access-token',
            'refresh_token' => 'new-zoom-refresh-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'https://api.zoom.us/v2/users/me' => Http::response([
            'id' => 'zoom-user-123',
            'email' => 'mentor.zoom@example.com',
        ], 200),
    ]);

    $this->actingAs($newMentorUser)
        ->withSession(['mentor_zoom_oauth_state' => 'zoom-state-123'])
        ->get(route('mentor.settings.zoom.callback', [
            'code' => 'zoom-auth-code',
            'state' => 'zoom-state-123',
        ]))
        ->assertRedirect(route('mentor.settings.index'));

    $this->assertDatabaseCount('oauth_tokens', 1);
    $this->assertDatabaseHas('oauth_tokens', [
        'user_id' => $newMentorUser->id,
        'provider' => 'zoom',
        'provider_user_id' => 'zoom-user-123',
        'access_token' => 'new-zoom-access-token',
    ]);
});

it('disconnects the mentor zoom token from settings', function () {
    $mentorUser = createSettingsUser('mentor');
    Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    OauthToken::query()->create([
        'user_id' => $mentorUser->id,
        'provider' => 'zoom',
        'provider_user_id' => 'zoom-user-123',
        'access_token' => 'zoom-access-token',
        'refresh_token' => 'zoom-refresh-token',
        'token_expires_at' => now()->addHour(),
    ]);

    $this->actingAs($mentorUser)
        ->delete(route('mentor.settings.zoom.disconnect'))
        ->assertRedirect(route('mentor.settings.index'));

    $this->assertDatabaseMissing('oauth_tokens', [
        'user_id' => $mentorUser->id,
        'provider' => 'zoom',
    ]);
});

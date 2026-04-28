<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
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
    $response->assertSee('Wharton');
    $response->assertSee('MBA (Wharton School)');
    $response->assertSee('Program Insights');
    $response->assertSee('Focused on admissions strategy.');
    $response->assertSee('Timezone');
});

it('updates mentor settings and syncs active services', function () {
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
        'status' => 'active',
    ]);

    $serviceA = ServiceConfig::query()->create([
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

    $serviceB = ServiceConfig::query()->create([
        'service_name' => 'Interview Prep',
        'service_slug' => 'interview-prep',
        'duration_minutes' => 45,
        'is_active' => true,
        'price_1on1' => 125,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 1,
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
        'office_hours_schedule' => 'Mondays at 7 PM EST',
        'edu_email' => 'mentor@yale.edu',
        'calendly_link' => 'https://calendly.com/mentor-example',
        'is_featured' => '1',
        'timezone' => 'Asia/Karachi',
        'service_config_ids' => [$serviceB->id, $serviceA->id],
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
    expect($mentor->office_hours_schedule)->toBe('Mondays at 7 PM EST');
    expect($mentor->edu_email)->toBe('mentor@yale.edu');
    expect($mentor->is_featured)->toBeTrue();
    expect($mentorUser->fresh()->setting?->timezone)->toBe('Asia/Karachi');

    $this->assertDatabaseHas('mentor_services', [
        'mentor_id' => $mentor->id,
        'service_config_id' => $serviceB->id,
        'sort_order' => 0,
    ]);

    $this->assertDatabaseHas('mentor_services', [
        'mentor_id' => $mentor->id,
        'service_config_id' => $serviceA->id,
        'sort_order' => 1,
    ]);
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

    $response = $this->actingAs($mentorUser)->from(route('mentor.settings.index'))->patch(route('mentor.settings.update'), [
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

it('does not overwrite a saved timezone from the detection endpoint', function () {
    $mentorUser = createSettingsUser('mentor');
    UserSetting::query()->create([
        'user_id' => $mentorUser->id,
        'theme' => 'light',
        'email_notifications' => true,
        'sms_notifications' => false,
        'timezone' => 'UTC',
    ]);

    $this->withoutMiddleware()
        ->actingAs($mentorUser)
        ->postJson(route('settings.timezone.store'), [
            'timezone' => 'Asia/Karachi',
        ])
        ->assertOk()
        ->assertJsonPath('timezone', 'UTC');

    expect($mentorUser->fresh()->setting?->timezone)->toBe('UTC');
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

it('disconnects the mentor zoom token from settings', function () {
    $mentorUser = createSettingsUser('mentor');

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

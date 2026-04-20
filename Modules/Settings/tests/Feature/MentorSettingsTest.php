<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Payments\app\Models\ServiceConfig;
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

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
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
    $response->assertSee('Program Insights');
    $response->assertSee('Focused on admissions strategy.');
});

it('updates mentor settings and syncs active services', function () {
    $mentorUser = createSettingsUser('mentor');

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
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
        'program_type' => 'law',
        'grad_school_display' => 'Yale Law',
        'bio' => 'Helping students sharpen essays and interviews.',
        'description' => 'Longer mentoring profile copy for expanded views.',
        'office_hours_schedule' => 'Mondays at 7 PM EST',
        'edu_email' => 'mentor@yale.edu',
        'calendly_link' => 'https://calendly.com/mentor-example',
        'is_featured' => '1',
        'service_config_ids' => [$serviceB->id, $serviceA->id],
    ]);

    $response->assertRedirect(route('mentor.settings.index'));

    expect($mentorUser->fresh()->name)->toBe('Mentor Example');
    expect($mentorUser->fresh()->email)->toBe('mentor@example.com');

    $mentor->refresh();

    expect($mentor->title)->toBe('Law Mentor');
    expect($mentor->program_type)->toBe('law');
    expect($mentor->grad_school_display)->toBe('Yale Law');
    expect($mentor->bio)->toBe('Helping students sharpen essays and interviews.');
    expect($mentor->office_hours_schedule)->toBe('Mondays at 7 PM EST');
    expect($mentor->edu_email)->toBe('mentor@yale.edu');
    expect($mentor->is_featured)->toBeTrue();

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

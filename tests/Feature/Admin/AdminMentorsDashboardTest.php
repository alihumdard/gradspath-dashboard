<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Models\MentorRating;
use Modules\Institutions\app\Models\University;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('mentor', 'web');
    Role::findOrCreate('student', 'web');
});

function createMentorDashboardUser(string $role = 'mentor'): User
{
    $user = User::factory()->create([
        'email' => Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

it('renders mentor db data with dynamic program and status filters', function () {
    $admin = createMentorDashboardUser('admin');
    $studentUser = createMentorDashboardUser('student');
    $mentorUser = createMentorDashboardUser('mentor');

    $university = University::query()->create([
        'name' => 'Harvard University',
        'display_name' => 'Harvard',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Cambridge',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ]);

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $university->id,
        'grad_school_display' => 'Harvard',
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
    ]);

    $service = ServiceConfig::query()->create([
        'service_name' => 'Program Insights',
        'service_slug' => 'program_insights',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 112,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 0,
    ]);

    Booking::query()->create([
        'student_id' => $studentUser->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->subDay(),
        'duration_minutes' => 60,
        'credits_charged' => 1,
        'status' => 'completed',
    ]);

    MentorRating::query()->create([
        'mentor_id' => $mentor->id,
        'avg_stars' => 4.8,
        'recommend_rate' => 100,
        'total_reviews' => 1,
        'total_sessions' => 1,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee($mentorUser->name);
    $response->assertSee($mentorUser->email);
    $response->assertSee('MBA');
    $response->assertSee('Harvard');
    $response->assertSee('Active');
    $response->assertSee('4.8');
    $response->assertSee('All Programs');
    $response->assertSee('All Statuses');
});

it('uses dash fallback for missing mentor fields', function () {
    $mentorUser = createMentorDashboardUser('mentor');

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => null,
        'grad_school_display' => null,
        'mentor_type' => 'graduate',
        'program_type' => null,
        'status' => 'pending',
    ]);

    $dashboardData = app(\Modules\Discovery\app\Services\AdminMentorsTableService::class)->build();
    $row = collect($dashboardData['rows'])->firstWhere('email', $mentorUser->email);

    expect($mentor)->not->toBeNull();
    expect($row)->not->toBeNull();
    expect($row['program'])->toBe('-');
    expect($row['school'])->toBe('-');
    expect($row['rating'])->toBe('-');
    expect($row['status'])->toBe('Pending');
    expect($row['total_revenue'])->toBeNull();
});

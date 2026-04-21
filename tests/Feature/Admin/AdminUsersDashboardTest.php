<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Bookings\app\Models\Booking;
use Modules\Institutions\app\Models\University;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Models\StudentProfile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
});

function createAdminDashboardUser(string $role = 'student'): User
{
    $user = User::factory()->create([
        'email' => Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

it('renders only student users with dynamic program and institution filters', function () {
    $admin = createAdminDashboardUser('admin');
    $university = University::query()->create([
        'name' => 'Boston College',
        'display_name' => 'Boston College',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Boston',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ]);

    $student = createAdminDashboardUser('student');
    StudentProfile::query()->create([
        'user_id' => $student->id,
        'university_id' => $university->id,
        'institution_text' => 'Boston College',
        'program_level' => 'grad',
        'program_type' => 'mba',
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

    $mentorUser = createAdminDashboardUser('mentor');
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $university->id,
        'grad_school_display' => 'Boston College',
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
    ]);

    Booking::query()->create([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->subDay(),
        'duration_minutes' => 60,
        'credits_charged' => 1,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users'));

    $response->assertOk();
    $response->assertSee($student->name);
    $response->assertSee($student->email);
    $response->assertSee('MBA');
    $response->assertSee('Boston College');
    $response->assertSee('All Programs');
    $response->assertSee('All Institutions');
});

it('uses dash fallback for missing student program and institution data', function () {
    $admin = createAdminDashboardUser('admin');
    $student = createAdminDashboardUser('student');

    StudentProfile::query()->create([
        'user_id' => $student->id,
        'university_id' => null,
        'institution_text' => null,
        'program_level' => 'undergrad',
        'program_type' => null,
    ]);

    $dashboardData = app(\Modules\Discovery\app\Services\AdminUsersTableService::class)->build();
    $row = collect($dashboardData['rows'])->firstWhere('email', $student->email);

    expect($row)->not->toBeNull();
    expect($row['program'])->toBe('-');
    expect($row['institution'])->toBe('-');
    expect($row['total_spent'])->toBeNull();
    expect($row['last_active'])->toBe('-');

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee($student->email);
});

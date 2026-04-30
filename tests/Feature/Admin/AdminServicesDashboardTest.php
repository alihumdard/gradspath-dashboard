<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Bookings\app\Models\Booking;
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

function createServiceDashboardUser(string $role): User
{
    $user = User::factory()->create([
        'email' => Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

it('renders services table from db data', function () {
    $admin = createServiceDashboardUser('admin');
    $student = createServiceDashboardUser('student');
    $mentorUser = createServiceDashboardUser('mentor');

    $university = University::query()->create([
        'name' => 'NYU',
        'display_name' => 'NYU',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'New York',
        'state_province' => 'New York',
        'is_active' => true,
    ]);

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $university->id,
        'grad_school_display' => 'NYU',
        'mentor_type' => 'graduate',
        'program_type' => 'law',
        'status' => 'active',
    ]);

    $service = ServiceConfig::query()->create([
        'service_name' => 'Interview Prep',
        'service_slug' => 'interview_prep',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 70,
        'price_1on3_per_person' => 62.99,
        'price_1on5_per_person' => 55.99,
        'office_hours_subscription_price' => null,
        'is_office_hours' => false,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 0,
    ]);

    $mentor->services()->attach($service->id, [
        'is_active' => true,
        'sort_order' => 0,
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

    $response = $this->actingAs($admin)->get(route('admin.services'));

    $response->assertOk();
    $response->assertSee('Interview Prep');
    $response->assertDontSee('60 min');
    $response->assertSee('1:1 $70');
    $response->assertSee('$70', false);
    $response->assertSee('1');
});

it('uses dash fallback for missing service values', function () {
    ServiceConfig::query()->create([
        'service_name' => 'Placeholder Service',
        'service_slug' => 'placeholder_service',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => null,
        'price_1on3_per_person' => null,
        'price_1on5_per_person' => null,
        'office_hours_subscription_price' => null,
        'is_office_hours' => false,
        'credit_cost_1on1' => 0,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 99,
    ]);

    $rows = app(\Modules\Discovery\app\Services\AdminServicesTableService::class)->build();
    $row = collect($rows)->firstWhere('service_name', 'Placeholder Service');

    expect($row)->not->toBeNull();
    expect($row['format'])->toBe('-');
    expect($row['set_price'])->toBe('-');
    expect($row['revenue'])->toBeNull();
    expect($row['bookings'])->toBe(0);
});

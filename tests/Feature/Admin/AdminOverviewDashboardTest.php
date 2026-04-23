<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
});

function createOverviewDashboardUser(string $role): User
{
    $user = User::factory()->create([
        'email' => Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

function createOverviewBooking(array $overrides = []): Booking
{
    $student = $overrides['student'] ?? createOverviewDashboardUser('student');
    $mentorUser = $overrides['mentor_user'] ?? createOverviewDashboardUser('mentor');
    $university = $overrides['university'] ?? University::query()->create([
        'name' => 'Overview University ' . Str::lower(Str::random(6)),
        'display_name' => 'Overview University ' . Str::lower(Str::random(6)),
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Boston',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ]);

    $mentor = $overrides['mentor'] ?? Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $university->id,
        'grad_school_display' => $university->display_name,
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
    ]);

    $service = $overrides['service'] ?? ServiceConfig::query()->create([
        'service_name' => 'Interview Prep ' . Str::lower(Str::random(6)),
        'service_slug' => 'overview_interview_prep_' . Str::lower(Str::random(8)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 120,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 0,
    ]);

    $booking = Booking::query()->create(array_merge([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->subDay(),
        'duration_minutes' => 60,
        'credits_charged' => 1,
        'amount_charged' => 120,
        'status' => 'completed',
    ], collect($overrides)->except([
        'student',
        'mentor_user',
        'university',
        'mentor',
        'service',
    ])->all()));

    DB::table('bookings')
        ->where('id', $booking->id)
        ->update([
            'created_at' => $overrides['created_at'] ?? now()->subDays(5),
            'updated_at' => $overrides['updated_at'] ?? now()->subDays(5),
            'cancelled_at' => $overrides['cancelled_at'] ?? null,
        ]);

    return $booking->fresh();
}

it('builds dynamic admin overview data and renders the live overview page', function () {
    $admin = createOverviewDashboardUser('admin');

    createOverviewDashboardUser('student');
    createOverviewDashboardUser('student');

    $mentorUser = createOverviewDashboardUser('mentor');
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => null,
        'grad_school_display' => 'Overview School',
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
    ]);

    Mentor::query()->create([
        'user_id' => createOverviewDashboardUser('mentor')->id,
        'university_id' => null,
        'grad_school_display' => 'Inactive School',
        'mentor_type' => 'graduate',
        'program_type' => 'law',
        'status' => 'paused',
    ]);

    $service = ServiceConfig::query()->create([
        'service_name' => 'Program Insights',
        'service_slug' => 'overview_program_insights',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 200,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 0,
    ]);

    createOverviewBooking([
        'mentor' => $mentor,
        'mentor_user' => $mentorUser,
        'service' => $service,
        'amount_charged' => 200,
        'created_at' => now()->subDays(4),
        'updated_at' => now()->subDays(4),
    ]);

    createOverviewBooking([
        'mentor' => $mentor,
        'mentor_user' => $mentorUser,
        'service' => $service,
        'amount_charged' => 80,
        'status' => 'cancelled_pending_refund',
        'created_at' => now()->subDays(8),
        'cancelled_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $overview = app(\Modules\Discovery\app\Services\AdminOverviewService::class)->build();

    expect($overview['summary']['total_users'])->toBe(4);
    expect($overview['summary']['active_mentors'])->toBe(1);
    expect($overview['summary']['inactive_mentors'])->toBe(1);
    expect($overview['summary']['bookings_30d'])->toBe(1);
    expect($overview['summary']['gross_revenue_30d'])->toBe(200.0);
    expect($overview['summary']['platform_revenue_30d'])->toBe(200.0);
    expect($overview['summary']['refund_amount_30d'])->toBe(80.0);
    expect($overview['summary']['refund_requests_30d'])->toBe(1);
    expect($overview['tables']['top_mentors'])->toMatchArray([
        [
            'mentor' => $mentorUser->name,
            'program' => 'MBA',
            'meetings' => 1,
            'revenue' => 200.0,
        ],
    ]);
    expect($overview['tables']['top_services'])->toMatchArray([
        [
            'service' => 'Program Insights',
            'bookings' => 1,
            'revenue' => 200.0,
            'set_price' => '1:1 $200',
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.overview'))
        ->assertOk()
        ->assertSee('Admin Overview')
        ->assertSee('Total Users')
        ->assertSee('4')
        ->assertSee('$200')
        ->assertSee('Program Insights')
        ->assertSee($mentorUser->name);
});

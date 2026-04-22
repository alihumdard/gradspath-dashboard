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

function createRankingsDashboardUser(string $role): User
{
    $user = User::factory()->create([
        'email' => Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

function createRankingBooking(array $overrides = []): Booking
{
    $student = $overrides['student'] ?? createRankingsDashboardUser('student');
    $mentorUser = $overrides['mentor_user'] ?? createRankingsDashboardUser('mentor');
    $university = $overrides['university'] ?? University::query()->create([
        'name' => 'Fallback University',
        'display_name' => 'Fallback University',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Boston',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ]);

    $mentor = $overrides['mentor'] ?? Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $university->id,
        'grad_school_display' => 'Fallback University',
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
    ]);

    $service = $overrides['service'] ?? ServiceConfig::query()->create([
        'service_name' => 'Program Insights',
        'service_slug' => 'program_insights_' . Str::lower(Str::random(8)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 100,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 0,
    ]);

    return Booking::query()->create(array_merge([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->subDay(),
        'duration_minutes' => 60,
        'credits_charged' => 1,
        'status' => 'completed',
    ], collect($overrides)->except([
        'student',
        'mentor_user',
        'university',
        'mentor',
        'service',
    ])->all()));
}

it('builds dynamic rankings and applies label fallbacks with stable ordering', function () {
    $programService = ServiceConfig::query()->create([
        'service_name' => 'Program Insights',
        'service_slug' => 'program_insights_rankings',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 120,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 0,
    ]);

    $applicationService = ServiceConfig::query()->create([
        'service_name' => 'Application Review',
        'service_slug' => 'application_review_rankings',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 90,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 1,
    ]);

    $harvard = University::query()->create([
        'name' => 'Harvard University',
        'display_name' => 'Harvard',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Cambridge',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ]);

    $columbia = University::query()->create([
        'name' => 'Columbia University',
        'display_name' => '',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'New York',
        'state_province' => 'New York',
        'is_active' => true,
    ]);

    $uol = University::query()->create([
        'name' => 'University of Lahore',
        'display_name' => 'UOL',
        'country' => 'PK',
        'alpha_two_code' => 'PK',
        'city' => 'Lahore',
        'state_province' => 'Punjab',
        'is_active' => true,
    ]);

    $mentorUser = createRankingsDashboardUser('mentor');
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $uol->id,
        'grad_school_display' => 'UOL',
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
    ]);

    $mbaStudentA = createRankingsDashboardUser('student');
    StudentProfile::query()->create([
        'user_id' => $mbaStudentA->id,
        'university_id' => $harvard->id,
        'institution_text' => 'Ignored',
        'program_level' => 'grad',
        'program_type' => 'mba',
    ]);

    $mbaStudentB = createRankingsDashboardUser('student');
    StudentProfile::query()->create([
        'user_id' => $mbaStudentB->id,
        'university_id' => $harvard->id,
        'institution_text' => null,
        'program_level' => 'grad',
        'program_type' => 'mba',
    ]);

    $lawStudent = createRankingsDashboardUser('student');
    StudentProfile::query()->create([
        'user_id' => $lawStudent->id,
        'university_id' => $columbia->id,
        'institution_text' => 'Ignored',
        'program_level' => 'professional',
        'program_type' => 'law',
    ]);

    $unknownStudent = createRankingsDashboardUser('student');
    StudentProfile::query()->create([
        'user_id' => $unknownStudent->id,
        'university_id' => null,
        'institution_text' => 'Custom School',
        'program_level' => 'undergrad',
        'program_type' => null,
    ]);

    createRankingBooking([
        'student' => $mbaStudentA,
        'mentor' => $mentor,
        'service' => $programService,
        'session_type' => '1on1',
    ]);
    createRankingBooking([
        'student' => $mbaStudentB,
        'mentor' => $mentor,
        'service' => $programService,
        'session_type' => '1on3',
    ]);
    createRankingBooking([
        'student' => $lawStudent,
        'mentor' => $mentor,
        'service' => $applicationService,
        'session_type' => '1on5',
    ]);
    createRankingBooking([
        'student' => $unknownStudent,
        'mentor' => $mentor,
        'service' => $applicationService,
        'session_type' => 'office_hours',
    ]);
    createRankingBooking([
        'student' => $lawStudent,
        'mentor' => $mentor,
        'service' => $programService,
        'session_type' => '1on1',
        'status' => 'cancelled',
    ]);

    $rankings = app(\Modules\Discovery\app\Services\AdminRankingsService::class)->build();

    expect($rankings['programs'])->toMatchArray([
        ['label' => 'MBA', 'count' => 2, 'rank' => 1],
        ['label' => 'Law', 'count' => 1, 'rank' => 2],
        ['label' => 'Unknown', 'count' => 1, 'rank' => 3],
    ]);

    expect($rankings['services'])->toMatchArray([
        ['label' => 'Application Review', 'count' => 2, 'rank' => 1],
        ['label' => 'Program Insights', 'count' => 2, 'rank' => 2],
    ]);

    expect($rankings['student_schools'])->toMatchArray([
        ['label' => 'Harvard', 'count' => 2, 'rank' => 1],
        ['label' => 'Columbia University', 'count' => 1, 'rank' => 2],
        ['label' => 'Custom School', 'count' => 1, 'rank' => 3],
    ]);

    expect($rankings['mentor_schools'])->toMatchArray([
        ['label' => 'UOL', 'count' => 4, 'rank' => 1],
    ]);

    expect($rankings['meeting_sizes'])->toMatchArray([
        ['label' => '1 on 1', 'count' => 1, 'rank' => 1],
        ['label' => '1 on 3', 'count' => 1, 'rank' => 2],
        ['label' => '1 on 5', 'count' => 1, 'rank' => 3],
        ['label' => 'Office Hours', 'count' => 1, 'rank' => 4],
    ]);
});

it('renders rankings from live admin dashboard data and shows empty placeholders', function () {
    $admin = createRankingsDashboardUser('admin');

    $this->actingAs($admin)
        ->get(route('admin.rankings'))
        ->assertOk()
        ->assertSee('Rankings')
        ->assertSee('Top Student Schools by Bookings')
        ->assertSee('Top Mentor Schools by Bookings')
        ->assertSeeText('No booking data yet');

    $service = app(\Modules\Discovery\app\Services\AdminRankingsService::class)->build();

    expect($service['programs'])->toBe([]);
    expect($service['services'])->toBe([]);
    expect($service['student_schools'])->toBe([]);
    expect($service['mentor_schools'])->toBe([]);
    expect($service['meeting_sizes'])->toBe([]);
});

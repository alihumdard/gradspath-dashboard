<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Discovery\app\Services\MentorDiscoveryService;
use Modules\Feedback\app\Models\Feedback;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function createDashboardUser(): User
{
    $created = \App\Models\User::factory()->create([
        'email' => Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    return User::query()->findOrFail($created->id);
}

function createDashboardMentor(string $name): Mentor
{
    $user = createDashboardUser();
    $user->forceFill(['name' => $name])->save();

    return Mentor::query()->create([
        'user_id' => $user->id,
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'title' => 'MBA Mentor',
        'status' => 'active',
    ]);
}

function createDashboardService(): ServiceConfig
{
    return ServiceConfig::query()->create([
        'service_name' => 'Program Insights '.Str::lower(Str::random(5)),
        'service_slug' => 'program_insights_'.Str::lower(Str::random(8)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 100,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 0,
    ]);
}

function createDashboardBooking(Mentor $mentor, ServiceConfig $service, Carbon $sessionAt, string $status = 'confirmed'): Booking
{
    return Booking::query()->create([
        'student_id' => createDashboardUser()->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => $sessionAt,
        'session_timezone' => 'UTC',
        'duration_minutes' => 60,
        'meeting_type' => 'zoom',
        'status' => $status,
        'approval_status' => 'not_required',
        'currency' => 'USD',
    ]);
}

function createDashboardUniversity(string $name, bool $isActive = true): University
{
    return University::query()->create([
        'name' => $name,
        'display_name' => $name,
        'country' => 'US',
        'is_active' => $isActive,
    ]);
}

function createDashboardProgram(University $university, bool $isActive = true): UniversityProgram
{
    return UniversityProgram::query()->create([
        'university_id' => $university->id,
        'program_name' => $university->name.' MBA',
        'program_type' => 'mba',
        'tier' => 'top',
        'is_active' => $isActive,
    ]);
}

it('renders the mentor dashboard with mentor-specific navigation', function () {
    $mentor = createDashboardUser();

    $response = $this->withoutMiddleware()->actingAs($mentor)->get(route('mentor.dashboard'));

    $response->assertOk();
    $response->assertSee('MENTOR PORTAL');
    $response->assertSee('/mentor/settings', false);
    $response->assertDontSee('/student/settings', false);
});

it('renders the student dashboard with student-specific navigation', function () {
    $student = createDashboardUser();

    $response = $this->withoutMiddleware()->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('STUDENT PORTAL');
    $response->assertSee('/student/settings', false);
    $response->assertDontSee('/mentor/settings', false);
});

it('links student dashboard mentor shortcuts to the matching mentor type filters', function () {
    $student = createDashboardUser();

    $response = $this->withoutMiddleware()->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee(route('student.mentors.index', ['mentor_type' => 'graduate']), false);
    $response->assertSee(route('student.mentors.index', ['mentor_type' => 'professional']), false);
});

it('links mentor dashboard mentor shortcuts to the matching mentor type filters', function () {
    $mentor = createDashboardUser();

    $response = $this->withoutMiddleware()->actingAs($mentor)->get(route('mentor.dashboard'));

    $response->assertOk();
    $response->assertSee(route('mentor.mentors.index', ['mentor_type' => 'graduate']), false);
    $response->assertSee(route('mentor.mentors.index', ['mentor_type' => 'professional']), false);
});

it('shows only active universities with active programs on the student dashboard', function () {
    $student = createDashboardUser();
    $visible = createDashboardUniversity('Visible Dashboard University');
    $inactive = createDashboardUniversity('Inactive Dashboard University', false);
    $withoutPrograms = createDashboardUniversity('No Program Dashboard University');
    $inactiveProgramOnly = createDashboardUniversity('Inactive Program Dashboard University');

    createDashboardProgram($visible);
    createDashboardProgram($inactive);
    createDashboardProgram($inactiveProgramOnly, false);

    $response = $this->withoutMiddleware()->actingAs($student)->get(route('student.dashboard'));

    $response->assertOk();
    $response->assertSee('Visible Dashboard University');
    $response->assertDontSee('Inactive Dashboard University');
    $response->assertDontSee('No Program Dashboard University');
    $response->assertDontSee('Inactive Program Dashboard University');
});

it('shows only active universities with active programs on the mentor dashboard', function () {
    $mentor = createDashboardUser();
    $visible = createDashboardUniversity('Visible Mentor Dashboard University');
    $inactive = createDashboardUniversity('Inactive Mentor Dashboard University', false);
    $withoutPrograms = createDashboardUniversity('No Program Mentor Dashboard University');
    $inactiveProgramOnly = createDashboardUniversity('Inactive Program Mentor Dashboard University');

    createDashboardProgram($visible);
    createDashboardProgram($inactive);
    createDashboardProgram($inactiveProgramOnly, false);

    $response = $this->withoutMiddleware()->actingAs($mentor)->get(route('mentor.dashboard'));

    $response->assertOk();
    $response->assertSee('Visible Mentor Dashboard University');
    $response->assertDontSee('Inactive Mentor Dashboard University');
    $response->assertDontSee('No Program Mentor Dashboard University');
    $response->assertDontSee('Inactive Program Mentor Dashboard University');
});

it('ranks mentors of the week by current week bookings', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 29, 12, 0, 0, 'UTC'));

    $service = createDashboardService();
    $lightlyBooked = createDashboardMentor('One Booking Mentor');
    $mostBooked = createDashboardMentor('Three Booking Mentor');
    $outsideWeek = createDashboardMentor('Outside Week Mentor');

    createDashboardBooking($lightlyBooked, $service, now()->addDay());
    createDashboardBooking($mostBooked, $service, now()->addDay());
    createDashboardBooking($mostBooked, $service, now()->addDays(2));
    createDashboardBooking($mostBooked, $service, now()->addDays(3));
    createDashboardBooking($mostBooked, $service, now()->addDay(), 'cancelled');
    createDashboardBooking($outsideWeek, $service, now()->addWeek());

    $mentors = app(MentorDiscoveryService::class)->featured();

    expect($mentors->pluck('name')->take(3)->all())->toBe([
        'Three Booking Mentor',
        'One Booking Mentor',
        'Outside Week Mentor',
    ]);
});

it('uses the latest visible student feedback on mentor dashboard cards', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 29, 12, 0, 0, 'UTC'));

    $service = createDashboardService();
    $mentor = createDashboardMentor('Feedback Mentor');
    $visibleBooking = createDashboardBooking($mentor, $service, now()->addDay());
    $hiddenBooking = createDashboardBooking($mentor, $service, now()->addDays(2));

    Feedback::query()->create([
        'booking_id' => $visibleBooking->id,
        'student_id' => $visibleBooking->student_id,
        'mentor_id' => $mentor->id,
        'stars' => 5,
        'preparedness_rating' => 5,
        'comment' => 'This mentor gave clear, actionable feedback from a real student.',
        'recommend' => true,
        'service_type' => 'office_hours',
        'is_verified' => true,
        'is_visible' => true,
    ]);

    Feedback::query()->create([
        'booking_id' => $hiddenBooking->id,
        'student_id' => $hiddenBooking->student_id,
        'mentor_id' => $mentor->id,
        'stars' => 5,
        'preparedness_rating' => 5,
        'comment' => 'Hidden admin-moderated feedback should not appear.',
        'recommend' => true,
        'service_type' => 'office_hours',
        'is_verified' => true,
        'is_visible' => false,
    ]);

    $dashboardMentor = app(MentorDiscoveryService::class)
        ->featured()
        ->firstWhere('id', $mentor->id);

    expect($dashboardMentor['review'])->toBe('This mentor gave clear, actionable feedback from a real student.');
});

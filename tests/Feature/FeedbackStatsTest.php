<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Models\Feedback;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
});

function createFeedbackStatsUser(string $role): User
{
    $user = User::factory()->create([
        'email' => 'feedback-stats-'.$role.'-'.Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

function createFeedbackStatsService(): ServiceConfig
{
    return ServiceConfig::query()->create([
        'service_name' => 'Feedback Stats Interview Prep',
        'service_slug' => 'feedback_stats_interview_prep_'.Str::lower(Str::random(8)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 100,
        'price_1on3_per_person' => 75,
        'price_1on3_total' => 225,
        'price_1on5_per_person' => 60,
        'price_1on5_total' => 300,
        'platform_fee_1on1' => 30,
        'mentor_payout_1on1' => 70,
        'platform_fee_1on3' => 75,
        'mentor_payout_1on3' => 150,
        'platform_fee_1on5' => 100,
        'mentor_payout_1on5' => 200,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 0,
    ]);
}

function createFeedbackStatsMentor(User $mentorUser): Mentor
{
    return Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'title' => 'MBA Mentor',
        'grad_school_display' => 'Feedback Stats University',
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
    ]);
}

function createFeedbackStatsBooking(User $student, Mentor $mentor, ServiceConfig $service, string $status = 'completed'): Booking
{
    return Booking::query()->create([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->subDay(),
        'session_timezone' => 'UTC',
        'duration_minutes' => 60,
        'meeting_type' => 'zoom',
        'status' => $status,
        'approval_status' => 'not_required',
        'completed_at' => $status === 'completed' ? now()->subHours(23) : null,
    ]);
}

it('shows completed session stats from completed bookings instead of visible feedback count', function () {
    $student = createFeedbackStatsUser('student');
    $mentorUser = createFeedbackStatsUser('mentor');
    $mentor = createFeedbackStatsMentor($mentorUser);
    $service = createFeedbackStatsService();

    $firstCompletedBooking = createFeedbackStatsBooking($student, $mentor, $service);
    $secondCompletedBooking = createFeedbackStatsBooking($student, $mentor, $service);
    createFeedbackStatsBooking($student, $mentor, $service);
    createFeedbackStatsBooking($student, $mentor, $service, 'confirmed');

    foreach ([$firstCompletedBooking, $secondCompletedBooking] as $index => $booking) {
        Feedback::query()->create([
            'booking_id' => $booking->id,
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'stars' => 5 - $index,
            'preparedness_rating' => 5,
            'comment' => 'Helpful feedback session '.$index,
            'recommend' => true,
            'service_type' => 'interview_prep',
            'is_verified' => true,
            'is_visible' => true,
        ]);
    }

    $this->actingAs($student)
        ->get(route('student.feedback.index'))
        ->assertOk()
        ->assertSee('Across 3 completed sessions')
        ->assertDontSee('Across 2 completed sessions');
});

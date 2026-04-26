<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\AdminLog;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Models\Feedback;
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
});

function createManualActionsAdmin(): User
{
    $user = User::factory()->create([
        'email' => 'manual-admin-'.Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user->assignRole('admin');

    return $user;
}

function createManualStudent(): User
{
    $user = User::factory()->create([
        'email' => 'manual-student-'.Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user->assignRole('student');

    return $user;
}

function createManualUniversity(array $attributes = []): University
{
    return University::query()->create(array_merge([
        'name' => 'University '.Str::random(6),
        'display_name' => 'University Display',
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Boston',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ], $attributes));
}

function createManualMentor(?User $user = null, ?University $university = null): Mentor
{
    $user ??= User::factory()->create([
        'email' => 'mentor-'.Str::uuid().'@example.com',
        'is_active' => true,
    ]);
    $university ??= createManualUniversity();

    return Mentor::query()->create([
        'user_id' => $user->id,
        'university_id' => $university->id,
        'title' => 'Graduate Mentor',
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'description' => 'Existing mentor description',
        'status' => 'pending',
    ]);
}

function createManualBooking(User $student, Mentor $mentor, ServiceConfig $service): Booking
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
        'status' => 'completed',
        'approval_status' => 'not_required',
    ]);
}

function createManualService(): ServiceConfig
{
    return ServiceConfig::query()->create([
        'service_name' => 'Interview Prep',
        'service_slug' => 'interview_prep',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 80,
        'price_1on3_per_person' => 55,
        'price_1on3_total' => 165,
        'price_1on5_per_person' => 40,
        'price_1on5_total' => 200,
        'platform_fee_1on1' => 30,
        'mentor_payout_1on1' => 50,
        'platform_fee_1on3' => 70,
        'mentor_payout_1on3' => 95,
        'platform_fee_1on5' => 90,
        'mentor_payout_1on5' => 110,
        'is_office_hours' => true,
        'office_hours_subscription_price' => 25,
        'office_hours_mentor_payout_per_attendee' => 15,
        'credit_cost_1on1' => 0,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 0,
    ]);
}

it('renders the manual actions hub with grouped sections', function () {
    $admin = createManualActionsAdmin();

    $this->actingAs($admin)
        ->get(route('admin.manual-actions'))
        ->assertOk()
        ->assertSee('Manual Actions Hub')
        ->assertSee('Account Actions')
        ->assertSee('Catalog Actions')
        ->assertSee('Moderation Actions')
        ->assertSee('Amend mentor account')
        ->assertSee('Adjust user credits')
        ->assertSee('Update service pricing')
        ->assertSee('Update feedback')
        ->assertDontSee('1 on 1 credits')
        ->assertDontSee('1 on 3 credits')
        ->assertDontSee('1 on 5 credits');
});

it('writes an admin log for credit adjustments and returns to the credits section', function () {
    $admin = createManualActionsAdmin();
    $student = createManualStudent();

    $this->actingAs($admin)
        ->post(route('admin.manual-actions.credits.adjust'), [
            'user_id' => $student->id,
            'amount' => 3,
            'notes' => 'Restore credits after support review.',
            'manual_section' => 'credits',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'credits');

    $this->assertDatabaseHas('user_credits', [
        'user_id' => $student->id,
        'balance' => 3,
    ]);

    $this->assertDatabaseHas('admin_logs', [
        'admin_id' => $admin->id,
        'action' => 'manual_credit_adjustment',
        'notes' => 'Restore credits after support review.',
    ]);
});

it('writes an admin log for mentor updates and returns to the mentor section', function () {
    $admin = createManualActionsAdmin();
    $mentor = createManualMentor();

    $this->actingAs($admin)
        ->post(route('admin.manual-actions.mentors.update'), [
            'mentor_id' => $mentor->id,
            'status' => 'active',
            'notes' => 'Approved after profile review.',
            'manual_section' => 'mentor',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'mentor');

    $this->assertDatabaseHas('mentors', [
        'id' => $mentor->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('admin_logs', [
        'admin_id' => $admin->id,
        'action' => 'amend_mentor',
        'target_table' => 'mentors',
        'target_id' => $mentor->id,
    ]);
});

it('writes admin logs for institution and service pricing actions', function () {
    $admin = createManualActionsAdmin();
    $service = createManualService();

    $this->actingAs($admin)
        ->post(route('admin.manual-actions.institutions.store'), [
            'name' => 'Manual Actions University',
            'display_name' => 'Manual University',
            'country' => 'US',
            'alpha_two_code' => 'US',
            'city' => 'Chicago',
            'state_province' => 'Illinois',
            'is_active' => '1',
            'notes' => 'Add new university requested by admin.',
            'manual_section' => 'institutions',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'institutions');

    $this->actingAs($admin)
        ->patch(route('admin.manual-actions.services.pricing.update'), [
            'service_id' => $service->id,
            'price_1on1' => 95,
            'platform_fee_1on1' => 40,
            'mentor_payout_1on1' => 55,
            'price_1on3_total' => 195,
            'platform_fee_1on3' => 80,
            'mentor_payout_1on3' => 115,
            'price_1on5_total' => 225,
            'platform_fee_1on5' => 100,
            'mentor_payout_1on5' => 125,
            'office_hours_subscription_price' => 30,
            'office_hours_mentor_payout_per_attendee' => 18,
            'notes' => 'Seasonal pricing update.',
            'manual_section' => 'pricing',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'pricing');

    $this->assertDatabaseHas('universities', [
        'name' => 'Manual Actions University',
        'display_name' => 'Manual University',
    ]);

    $this->assertDatabaseHas('services_config', [
        'id' => $service->id,
        'price_1on1' => 95,
        'platform_fee_1on1' => 40,
        'mentor_payout_1on1' => 55,
        'price_1on3_total' => 195,
        'platform_fee_1on3' => 80,
        'mentor_payout_1on3' => 115,
        'price_1on5_total' => 225,
        'platform_fee_1on5' => 100,
        'mentor_payout_1on5' => 125,
        'office_hours_subscription_price' => 30,
        'office_hours_mentor_payout_per_attendee' => 18,
    ]);

    expect(AdminLog::query()->where('action', 'manual_institution_create')->exists())->toBeTrue();
    expect(AdminLog::query()->where('action', 'manual_service_update')->exists())->toBeTrue();
});

it('rejects service pricing updates when admin and mentor splits do not equal the student price', function () {
    $admin = createManualActionsAdmin();
    $service = createManualService();

    $this->actingAs($admin)
        ->from(route('admin.manual-actions'))
        ->patch(route('admin.manual-actions.services.pricing.update'), [
            'service_id' => $service->id,
            'price_1on1' => 95,
            'platform_fee_1on1' => 40,
            'mentor_payout_1on1' => 40,
            'notes' => 'Invalid split update.',
            'manual_section' => 'pricing',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHasErrors(['platform_fee_1on1', 'mentor_payout_1on1']);

    expect((float) $service->fresh()->price_1on1)->toBe(80.0);
});

it('writes an admin log for feedback moderation and keeps the feedback section active', function () {
    $admin = createManualActionsAdmin();
    $student = createManualStudent();
    $mentor = createManualMentor();
    $service = createManualService();
    $booking = createManualBooking($student, $mentor, $service);

    $feedback = Feedback::query()->create([
        'booking_id' => $booking->id,
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'stars' => 5,
        'comment' => 'Original comment',
        'recommend' => true,
        'is_verified' => true,
        'is_visible' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.manual-actions.feedback.update'), [
            'feedback_id' => $feedback->id,
            'comment' => 'Edited by admin',
            'is_visible' => '0',
            'admin_note' => 'Hidden after moderation review.',
            'manual_section' => 'feedback',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'feedback');

    $this->assertDatabaseHas('feedback', [
        'id' => $feedback->id,
        'comment' => 'Edited by admin',
        'is_visible' => 0,
        'admin_note' => 'Hidden after moderation review.',
    ]);

    $this->assertDatabaseHas('admin_logs', [
        'admin_id' => $admin->id,
        'action' => 'manual_feedback_update',
        'target_table' => 'feedback',
        'target_id' => $feedback->id,
    ]);
});

it('writes an admin log for booking outcome updates and keeps the bookings section active', function () {
    $admin = createManualActionsAdmin();
    $student = createManualStudent();
    $mentor = createManualMentor();
    $service = createManualService();

    $booking = Booking::query()->create([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->subHour(),
        'session_timezone' => 'UTC',
        'duration_minutes' => 60,
        'meeting_type' => 'zoom',
        'status' => 'confirmed',
        'approval_status' => 'not_required',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.manual-actions.bookings.outcome.update'), [
            'booking_id' => $booking->id,
            'session_outcome' => 'interrupted',
            'session_outcome_note' => 'Mentor reported a network interruption.',
            'completion_source' => 'manual',
            'manual_section' => 'bookings',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'bookings');

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'completed',
        'session_outcome' => 'interrupted',
        'completion_source' => 'manual',
        'session_outcome_note' => 'Mentor reported a network interruption.',
    ]);

    $this->assertDatabaseHas('admin_logs', [
        'admin_id' => $admin->id,
        'action' => 'manual_booking_outcome_update',
        'target_table' => 'bookings',
        'target_id' => $booking->id,
    ]);
});

it('blocks non admin users from the manual actions hub and endpoints', function () {
    $student = createManualStudent();

    $this->actingAs($student)
        ->get(route('admin.manual-actions'))
        ->assertForbidden();

    $this->actingAs($student)
        ->post(route('admin.manual-actions.credits.adjust'), [
            'user_id' => $student->id,
            'amount' => 1,
            'notes' => 'Should not be allowed.',
            'manual_section' => 'credits',
        ])
        ->assertForbidden();
});

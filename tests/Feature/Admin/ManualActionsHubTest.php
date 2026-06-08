<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\AdminLog;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Models\MentorRating;
use Modules\Feedback\app\Services\RatingAggregationService;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;
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

function createManualService(array $attributes = []): ServiceConfig
{
    return ServiceConfig::query()->create(array_merge([
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
    ], $attributes));
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
        ->assertSee('Upload logo')
        ->assertSee('Logo URL or public path')
        ->assertDontSee('Alpha-2 code')
        ->assertSee('Update feedback')
        ->assertDontSee('Admin note')
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
    ]);
});

it('writes an admin log for mentor updates and returns to the mentor section', function () {
    $admin = createManualActionsAdmin();
    $mentor = createManualMentor();

    $this->actingAs($admin)
        ->post(route('admin.manual-actions.mentors.update'), [
            'mentor_id' => $mentor->id,
            'status' => 'active',
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

it('lets admins set and clear mentor rating overrides', function () {
    $admin = createManualActionsAdmin();
    $mentor = createManualMentor();

    MentorRating::query()->create([
        'mentor_id' => $mentor->id,
        'avg_stars' => 4.10,
        'recommend_rate' => 100,
        'total_reviews' => 2,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.manual-actions.mentors.update'), [
            'mentor_id' => $mentor->id,
            'status' => 'active',
            'admin_rating_override' => '4.95',
            'admin_rating_override_note' => 'Launch ordering adjustment',
            'manual_section' => 'mentor',
        ])
        ->assertRedirect(route('admin.manual-actions'));

    $rating = $mentor->fresh('rating')->rating;

    expect((float) $rating->avg_stars)->toBe(4.10)
        ->and((float) $rating->admin_rating_override)->toBe(4.95)
        ->and($rating->admin_rating_override_note)->toBe('Launch ordering adjustment')
        ->and($rating->admin_rating_overridden_by)->toBe($admin->id)
        ->and($rating->admin_rating_overridden_at)->not->toBeNull()
        ->and((float) $rating->effective_rating)->toBe(4.95);

    $this->assertDatabaseHas('admin_logs', [
        'admin_id' => $admin->id,
        'action' => 'amend_mentor',
        'target_table' => 'mentors',
        'target_id' => $mentor->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.manual-actions.mentors.update'), [
            'mentor_id' => $mentor->id,
            'status' => 'active',
            'clear_admin_rating_override' => '1',
            'manual_section' => 'mentor',
        ])
        ->assertRedirect(route('admin.manual-actions'));

    $rating = $mentor->fresh('rating')->rating;

    expect($rating->admin_rating_override)->toBeNull()
        ->and($rating->admin_rating_override_note)->toBeNull()
        ->and($rating->admin_rating_overridden_by)->toBeNull()
        ->and($rating->admin_rating_overridden_at)->toBeNull()
        ->and((float) $rating->effective_rating)->toBe(4.10);
});

it('preserves admin rating overrides when feedback recalculates calculated ratings', function () {
    $mentor = createManualMentor();
    $student = createManualStudent();
    $service = createManualService();
    $booking = createManualBooking($student, $mentor, $service);

    MentorRating::query()->create([
        'mentor_id' => $mentor->id,
        'avg_stars' => 3.00,
        'admin_rating_override' => 4.80,
        'admin_rating_override_note' => 'Do not erase me',
        'recommend_rate' => 100,
        'total_reviews' => 1,
    ]);

    Feedback::query()->create([
        'booking_id' => $booking->id,
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'stars' => 5,
        'comment' => 'Helpful session.',
        'recommend' => true,
        'service_type' => 'office_hours',
        'is_verified' => true,
        'is_visible' => true,
    ]);

    app(RatingAggregationService::class)->recalculate($mentor->id);

    $rating = $mentor->fresh('rating')->rating;

    expect((float) $rating->avg_stars)->toBe(5.00)
        ->and((float) $rating->admin_rating_override)->toBe(4.80)
        ->and($rating->admin_rating_override_note)->toBe('Do not erase me')
        ->and((float) $rating->effective_rating)->toBe(4.80);
});

it('writes admin logs for institution and service pricing actions', function () {
    $admin = createManualActionsAdmin();
    $service = createManualService();
    $logo = UploadedFile::fake()->create('manual-actions-logo.png', 4, 'image/png');

    $this->actingAs($admin)
        ->post(route('admin.manual-actions.institutions.store'), [
            'name' => 'Manual Actions University',
            'display_name' => 'Manual University',
            'country' => 'US',
            'city' => 'Chicago',
            'state_province' => 'Illinois',
            'logo_file' => $logo,
            'is_active' => '1',
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
            'manual_section' => 'pricing',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'pricing');

    $university = University::query()->where('name', 'Manual Actions University')->firstOrFail();
    expect($university->display_name)->toBe('Manual University')
        ->and($university->logo_url)->toStartWith('university_logo/manual-actions-university-');
    expect(is_file(public_path($university->logo_url)))->toBeTrue();
    @unlink(public_path($university->logo_url));

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

it('renders and saves manual institution and program edits', function () {
    $admin = createManualActionsAdmin();
    $university = createManualUniversity([
        'name' => 'Existing University',
        'display_name' => 'Existing U',
        'logo_url' => 'university_logo/existing.png',
    ]);
    $program = UniversityProgram::query()->create([
        'university_id' => $university->id,
        'program_name' => 'Existing MBA',
        'program_type' => 'mba',
        'tier' => 'top',
        'description' => 'Original description',
        'duration_months' => 18,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.manual-actions'))
        ->assertOk()
        ->assertSee('Edit institution')
        ->assertSee('Edit program')
        ->assertSee('manualInstitutionEditForm', false)
        ->assertSee('manualProgramEditForm', false)
        ->assertSee('university_logo/existing.png', false);

    $this->actingAs($admin)
        ->patch(route('admin.manual-actions.institutions.update', $university), [
            'name' => 'Edited University',
            'display_name' => 'Edited U',
            'country' => 'US',
            'alpha_two_code' => 'us',
            'city' => 'New York',
            'state_province' => 'New York',
            'domains' => "edited.edu\nalumni.edited.edu",
            'web_pages' => "https://edited.edu",
            'logo_url' => 'university_logo/edited.png',
            'is_active' => '1',
            'manual_section' => 'institutions',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'institutions');

    $this->actingAs($admin)
        ->patch(route('admin.manual-actions.programs.update', $program), [
            'university_id' => $university->id,
            'program_name' => 'Edited MBA',
            'program_type' => 'law',
            'tier' => 'elite',
            'duration_months' => 24,
            'description' => 'Edited description',
            'is_active' => '0',
            'manual_section' => 'programs',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'programs');

    $this->assertDatabaseHas('universities', [
        'id' => $university->id,
        'name' => 'Edited University',
        'display_name' => 'Edited U',
        'alpha_two_code' => 'US',
        'city' => 'New York',
        'logo_url' => 'university_logo/edited.png',
    ]);

    expect($university->fresh()->domains)->toBe(['edited.edu', 'alumni.edited.edu'])
        ->and($university->fresh()->web_pages)->toBe(['https://edited.edu']);

    $this->assertDatabaseHas('university_programs', [
        'id' => $program->id,
        'program_name' => 'Edited MBA',
        'program_type' => 'law',
        'tier' => 'elite',
        'duration_months' => 24,
        'description' => 'Edited description',
        'is_active' => false,
    ]);

    expect(AdminLog::query()->where('action', 'manual_institution_update')->exists())->toBeTrue();
    expect(AdminLog::query()->where('action', 'manual_program_update')->exists())->toBeTrue();
});

it('limits manual action institution and program searches to ten results', function () {
    $admin = createManualActionsAdmin();

    foreach (range(1, 12) as $index) {
        $university = createManualUniversity([
            'name' => "Searchable University {$index}",
            'display_name' => "Searchable U {$index}",
        ]);

        UniversityProgram::query()->create([
            'university_id' => $university->id,
            'program_name' => "Searchable Program {$index}",
            'program_type' => 'mba',
            'tier' => 'top',
            'is_active' => true,
        ]);
    }

    $this->actingAs($admin)
        ->getJson(route('admin.manual-actions.universities.search', [
            'q' => 'Searchable',
            'per_page' => 50,
            'include_inactive' => 1,
        ]))
        ->assertOk()
        ->assertJsonCount(10, 'data');

    $this->actingAs($admin)
        ->getJson(route('admin.manual-actions.programs.search', [
            'q' => 'Searchable',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertJsonCount(10, 'data');
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
            'manual_section' => 'pricing',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHasErrors(['platform_fee_1on1', 'mentor_payout_1on1']);

    expect((float) $service->fresh()->price_1on1)->toBe(80.0);
});

it('allows pricing updates when inactive service groups are left blank', function () {
    $admin = createManualActionsAdmin();
    $service = createManualService([
        'service_name' => 'Gap Year Planning',
        'service_slug' => 'gap_year_planning',
        'price_1on1' => 50,
        'platform_fee_1on1' => 19,
        'mentor_payout_1on1' => 31,
        'price_1on3_per_person' => null,
        'price_1on3_total' => null,
        'platform_fee_1on3' => null,
        'mentor_payout_1on3' => null,
        'price_1on5_per_person' => null,
        'price_1on5_total' => null,
        'platform_fee_1on5' => null,
        'mentor_payout_1on5' => null,
        'is_office_hours' => false,
        'office_hours_subscription_price' => null,
        'office_hours_mentor_payout_per_attendee' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.manual-actions.services.pricing.update'), [
            'service_id' => $service->id,
            'price_1on1' => 55,
            'platform_fee_1on1' => 20,
            'mentor_payout_1on1' => 35,
            'price_1on3_total' => '',
            'platform_fee_1on3' => '',
            'mentor_payout_1on3' => '',
            'price_1on5_total' => '',
            'platform_fee_1on5' => '',
            'mentor_payout_1on5' => '',
            'office_hours_subscription_price' => '',
            'office_hours_mentor_payout_per_attendee' => '',
            'manual_section' => 'pricing',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('manual_section', 'pricing');

    $this->assertDatabaseHas('services_config', [
        'id' => $service->id,
        'price_1on1' => 55,
        'platform_fee_1on1' => 20,
        'mentor_payout_1on1' => 35,
        'price_1on3_total' => null,
        'platform_fee_1on3' => null,
        'mentor_payout_1on3' => null,
        'price_1on5_total' => null,
        'platform_fee_1on5' => null,
        'mentor_payout_1on5' => null,
    ]);
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
            'manual_section' => 'feedback',
        ])
        ->assertRedirect(route('admin.manual-actions'))
        ->assertSessionHas('manual_section', 'feedback');

    $this->assertDatabaseHas('feedback', [
        'id' => $feedback->id,
        'comment' => 'Edited by admin',
        'is_visible' => 0,
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
            'manual_section' => 'credits',
        ])
        ->assertForbidden();
});

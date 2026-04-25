<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\MentorNotes\app\Models\MentorNote;
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

function createMentorUser(string $prefix): array
{
    $created = \App\Models\User::factory()->create([
        'name' => Str::title(str_replace('-', ' ', $prefix)).' Mentor',
        'email' => $prefix.'-'.Str::uuid().'@example.com',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $user = User::query()->findOrFail($created->id);
    $user->assignRole('mentor');

    $mentor = Mentor::query()->create([
        'user_id' => $user->id,
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'title' => 'Graduate Mentor',
        'grad_school_display' => 'Harvard Business School',
        'status' => 'active',
    ]);

    return [$user, $mentor];
}

function createStudentUser(string $prefix = 'student'): User
{
    $created = \App\Models\User::factory()->create([
        'name' => Str::title(str_replace('-', ' ', $prefix)).' Student',
        'email' => $prefix.'-'.Str::uuid().'@example.edu',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $user = User::query()->findOrFail($created->id);
    $user->assignRole('student');

    return $user;
}

function createService(string $name = 'Application Review', string $slug = 'application_review'): ServiceConfig
{
    return ServiceConfig::query()->create([
        'service_name' => $name,
        'service_slug' => $slug.'-'.Str::lower(Str::random(6)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 120,
        'price_1on3_per_person' => 85,
        'price_1on3_total' => 255,
        'price_1on5_per_person' => 70,
        'price_1on5_total' => 350,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 1,
    ]);
}

function createBookingForMentor(Mentor $mentor, User $student, ServiceConfig $service, array $overrides = []): Booking
{
    return Booking::query()->create(array_merge([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->addDays(2)->utc()->toDateTimeString(),
        'session_timezone' => 'Asia/Karachi',
        'duration_minutes' => 60,
        'meeting_type' => 'zoom',
        'meeting_link' => 'https://example.com/meeting',
        'calendar_sync_status' => 'synced',
        'status' => 'confirmed',
        'approval_status' => 'not_required',
        'attendance_status' => 'pending',
        'currency' => 'USD',
        'amount_charged' => 120,
    ], $overrides));
}

it('loads the mentor notes form for a hosted booking and saves the note', function () {
    [$mentorUser, $mentor] = createMentorUser('hosted-notes');
    $student = createStudentUser('notes-student');
    $service = createService();
    $booking = createBookingForMentor($mentor, $student, $service);

    $this->actingAs($mentorUser)
        ->get(route('mentor.notes.bookings.edit', $booking->id))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee($mentorUser->email)
        ->assertSee('Mentor Notes After Session');

    $this->actingAs($mentorUser)
        ->post(route('mentor.notes.bookings.store', $booking->id), [
            'worked_on' => 'Reviewed the application strategy and essay structure.',
            'next_steps' => 'Student should revise the introduction and tighten school fit examples.',
            'session_result' => 'Student left with a clearer application plan.',
            'strengths_challenges' => 'Strength: strong raw material. Challenge: narrative focus.',
            'other_notes' => 'A follow-up after revisions would help.',
        ])
        ->assertRedirect(route('mentor.notes.bookings.edit', $booking->id));

    $this->assertDatabaseHas('mentor_notes', [
        'mentor_id' => $mentor->id,
        'student_id' => $student->id,
        'booking_id' => $booking->id,
        'service_type' => 'Application Review',
        'worked_on' => 'Reviewed the application strategy and essay structure.',
    ]);

    expect($booking->fresh()->mentor_feedback_done)->toBeTrue();
});

it('updates the existing mentor note instead of creating a duplicate for the same booking', function () {
    [$mentorUser, $mentor] = createMentorUser('update-notes');
    $student = createStudentUser('update-student');
    $service = createService('Interview Prep', 'interview_prep');
    $booking = createBookingForMentor($mentor, $student, $service);

    MentorNote::query()->create([
        'mentor_id' => $mentor->id,
        'student_id' => $student->id,
        'booking_id' => $booking->id,
        'session_date' => now()->toDateString(),
        'service_type' => 'Interview Prep',
        'worked_on' => 'Initial notes',
        'next_steps' => 'Initial next steps',
        'session_result' => 'Initial result',
        'strengths_challenges' => 'Initial reflection',
        'other_notes' => 'Initial other notes',
    ]);

    $this->actingAs($mentorUser)
        ->post(route('mentor.notes.bookings.store', $booking->id), [
            'worked_on' => 'Updated interview practice notes.',
            'next_steps' => 'Continue mock interview drills.',
            'session_result' => 'Answers became more structured.',
            'strengths_challenges' => 'Strength: thoughtful examples. Challenge: pacing.',
            'other_notes' => 'One more session before the interview date.',
        ])
        ->assertRedirect(route('mentor.notes.bookings.edit', $booking->id));

    expect(MentorNote::query()->count())->toBe(1);

    $this->assertDatabaseHas('mentor_notes', [
        'mentor_id' => $mentor->id,
        'booking_id' => $booking->id,
        'worked_on' => 'Updated interview practice notes.',
        'other_notes' => 'One more session before the interview date.',
    ]);
});

it('forbids mentors from creating or editing notes for bookings they do not host', function () {
    [$viewerUser, $viewerMentor] = createMentorUser('viewer');
    [, $hostMentor] = createMentorUser('host');
    $student = createStudentUser('protected-student');
    $service = createService();
    $booking = createBookingForMentor($hostMentor, $student, $service);

    $this->actingAs($viewerUser)
        ->get(route('mentor.notes.bookings.edit', $booking->id))
        ->assertForbidden();

    $this->actingAs($viewerUser)
        ->post(route('mentor.notes.bookings.store', $booking->id), [
            'worked_on' => 'Should not save.',
            'next_steps' => 'Should not save.',
            'session_result' => 'Should not save.',
            'strengths_challenges' => 'Should not save.',
            'other_notes' => 'Should not save.',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('mentor_notes', [
        'mentor_id' => $viewerMentor->id,
        'booking_id' => $booking->id,
    ]);
});

it('shows notes from multiple mentors on the mentor notes browser page', function () {
    [$viewerUser, $viewerMentor] = createMentorUser('browser-viewer');
    [$otherMentorUser, $otherMentor] = createMentorUser('browser-other');
    $student = createStudentUser('browser-student');
    $service = createService();
    $viewerBooking = createBookingForMentor($viewerMentor, $student, $service);
    $otherBooking = createBookingForMentor($otherMentor, $student, $service, [
        'session_at' => now()->addDays(4)->utc()->toDateTimeString(),
    ]);

    MentorNote::query()->create([
        'mentor_id' => $viewerMentor->id,
        'student_id' => $student->id,
        'booking_id' => $viewerBooking->id,
        'session_date' => now()->toDateString(),
        'service_type' => 'Application Review',
        'worked_on' => 'Viewer mentor note body.',
        'next_steps' => 'Viewer mentor next steps.',
        'session_result' => 'Viewer mentor result.',
        'strengths_challenges' => 'Viewer mentor reflection.',
        'other_notes' => 'Viewer mentor other notes.',
    ]);

    MentorNote::query()->create([
        'mentor_id' => $otherMentor->id,
        'student_id' => $student->id,
        'booking_id' => $otherBooking->id,
        'session_date' => now()->addDay()->toDateString(),
        'service_type' => 'Application Review',
        'worked_on' => 'Other mentor note body.',
        'next_steps' => 'Other mentor next steps.',
        'session_result' => 'Other mentor result.',
        'strengths_challenges' => 'Other mentor reflection.',
        'other_notes' => 'Other mentor other notes.',
    ]);

    $this->actingAs($viewerUser)
        ->get(route('mentor.notes'))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee($viewerUser->name)
        ->assertSee($otherMentorUser->name)
        ->assertSee('Viewer mentor note body.')
        ->assertSee('Other mentor note body.');
});

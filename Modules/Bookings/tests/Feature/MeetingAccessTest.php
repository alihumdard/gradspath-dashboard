<?php

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\OauthToken;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Jobs\SendBookingConfirmationJob;
use Modules\Bookings\app\Jobs\SendBookingReminderJob;
use Modules\Bookings\app\Mail\BookingReminderMail;
use Modules\Bookings\app\Mail\StudentBookingConfirmationMail;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\BookingMeetingPresenter;
use Modules\Institutions\app\Models\University;
use Modules\Institutions\app\Models\UniversityProgram;
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
});

afterEach(function () {
    Carbon::setTestNow();
});

function createBookingAccessUser(string $role): User
{
    $created = \App\Models\User::factory()->create([
        'email' => Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user = User::query()->findOrFail($created->id);
    $user->assignRole($role);

    return $user;
}

function createBookingAccessContext(string $timezone = 'Asia/Karachi'): array
{
    $studentUser = createBookingAccessUser('student');
    $mentorUser = createBookingAccessUser('mentor');

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'title' => 'MBA Mentor',
        'program_type' => 'mba',
        'grad_school_display' => 'Wharton',
        'status' => 'active',
    ]);

    $service = ServiceConfig::query()->create([
        'service_name' => 'Program Insights',
        'service_slug' => 'program-insights',
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 100,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 0,
    ]);

    $sessionAtLocal = Carbon::create(2026, 4, 29, 13, 0, 0, $timezone);

    $booking = Booking::query()->create([
        'student_id' => $studentUser->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => $sessionAtLocal,
        'session_timezone' => $timezone,
        'duration_minutes' => 60,
        'meeting_link' => 'https://zoom.us/j/9876543210',
        'meeting_type' => 'zoom',
        'external_calendar_event_id' => 'zoom-meeting-123',
        'calendar_provider' => 'zoom',
        'calendar_sync_status' => 'synced',
        'status' => 'confirmed',
        'approval_status' => 'not_required',
        'currency' => 'USD',
    ]);

    DB::table('booking_participants')->insert([
        'booking_id' => $booking->id,
        'user_id' => $studentUser->id,
        'full_name' => $studentUser->name,
        'email' => $studentUser->email,
        'participant_role' => 'student',
        'is_primary' => true,
        'invite_status' => 'accepted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('studentUser', 'mentorUser', 'mentor', 'service', 'booking', 'sessionAtLocal');
}

function createBookingForTime(
    User $studentUser,
    Mentor $mentor,
    ServiceConfig $service,
    Carbon $sessionAtLocal,
    string $meetingId
): Booking {
    $booking = Booking::query()->create([
        'student_id' => $studentUser->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => $sessionAtLocal,
        'session_timezone' => $sessionAtLocal->getTimezone()->getName(),
        'duration_minutes' => 60,
        'meeting_link' => 'https://zoom.us/j/'.$meetingId,
        'meeting_type' => 'zoom',
        'external_calendar_event_id' => $meetingId,
        'calendar_provider' => 'zoom',
        'calendar_sync_status' => 'synced',
        'status' => 'confirmed',
        'approval_status' => 'not_required',
        'currency' => 'USD',
    ]);

    DB::table('booking_participants')->insert([
        'booking_id' => $booking->id,
        'user_id' => $studentUser->id,
        'full_name' => $studentUser->name,
        'email' => $studentUser->email,
        'participant_role' => 'student',
        'is_primary' => true,
        'invite_status' => 'accepted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $booking;
}

it('blocks students from joining before the exact start time even when the meeting link exists', function () {
    $context = createBookingAccessContext();
    $booking = $context['booking'];
    $startLocal = $context['sessionAtLocal'];

    Carbon::setTestNow($context['sessionAtLocal']->copy()->utc()->subSecond());

    $response = $this
        ->actingAs($context['studentUser'])
        ->get(route('student.bookings.join-meeting', $booking->id));

    $response->assertRedirect(route('student.bookings.show', $booking->id));
    $response->assertSessionHas('error', sprintf(
        'Meeting access will be enabled at %s on %s.',
        $startLocal->format('g:i A'),
        $startLocal->format('F j, Y')
    ));

    $page = $this
        ->actingAs($context['studentUser'])
        ->get(route('student.bookings.show', $booking->id));

    $page->assertOk();
    $page->assertSee('meetingAccessAllowed', false);
    $page->assertSee(sprintf(
        'Meeting access will be enabled at %s on %s.',
        $startLocal->format('g:i A'),
        $startLocal->format('F j, Y')
    ), false);
});

it('allows students to join exactly at the start time and after the meeting starts', function () {
    $context = createBookingAccessContext();
    $booking = $context['booking'];
    $startUtc = $context['sessionAtLocal']->copy()->utc();

    Carbon::setTestNow($startUtc);

    $atStart = $this
        ->actingAs($context['studentUser'])
        ->get(route('student.bookings.join-meeting', $booking->id));

    $atStart->assertRedirect('https://zoom.us/j/9876543210');

    Carbon::setTestNow($startUtc->copy()->addMinutes(15));

    $afterStart = $this
        ->actingAs($context['studentUser'])
        ->get(route('student.bookings.join-meeting', $booking->id));

    $afterStart->assertRedirect('https://zoom.us/j/9876543210');
});

it('unlocks bookings independently based on each booking start time', function () {
    $context = createBookingAccessContext();
    $sessionAtOnePm = Carbon::create(2026, 4, 29, 13, 0, 0, 'Asia/Karachi');
    $sessionAtTwoPm = Carbon::create(2026, 4, 29, 14, 0, 0, 'Asia/Karachi');

    $bookingAtOnePm = createBookingForTime(
        $context['studentUser'],
        $context['mentor'],
        $context['service'],
        $sessionAtOnePm,
        'zoom-meeting-1pm'
    );

    $bookingAtTwoPm = createBookingForTime(
        $context['studentUser'],
        $context['mentor'],
        $context['service'],
        $sessionAtTwoPm,
        'zoom-meeting-2pm'
    );

    Carbon::setTestNow(Carbon::create(2026, 4, 29, 13, 30, 0, 'Asia/Karachi')->utc());

    expect($bookingAtOnePm->fresh()->meetingAccessAllowed())->toBeTrue();
    expect($bookingAtTwoPm->fresh()->meetingAccessAllowed())->toBeFalse();
    expect($bookingAtTwoPm->fresh()->meetingAccessMessage())->toBe('Meeting access will be enabled at 2:00 PM on April 29, 2026.');

    $response = $this
        ->actingAs($context['studentUser'])
        ->get(route('student.bookings.join-meeting', $bookingAtTwoPm->id));

    $response->assertRedirect(route('student.bookings.show', $bookingAtTwoPm->id));
    $response->assertSessionHas('error', 'Meeting access will be enabled at 2:00 PM on April 29, 2026.');
});

it('blocks mentors from starting before the scheduled start time even when zoom is ready', function () {
    $context = createBookingAccessContext();
    $booking = $context['booking'];
    $startUtc = $context['sessionAtLocal']->copy()->utc();
    $startLocal = $context['sessionAtLocal'];

    config([
        'services.zoom.enabled' => true,
        'services.zoom.client_id' => 'zoom-client-id',
        'services.zoom.client_secret' => 'zoom-client-secret',
        'services.zoom.redirect_uri' => 'https://gradspath.test/mentor/settings/zoom/callback',
        'services.zoom.api_base' => 'https://api.zoom.us/v2',
    ]);

    OauthToken::query()->create([
        'user_id' => $context['mentorUser']->id,
        'provider' => 'zoom',
        'provider_user_id' => 'zoom-user-123',
        'access_token' => 'zoom-access-token',
        'refresh_token' => 'zoom-refresh-token',
        'token_expires_at' => now()->addHour(),
    ]);

    Http::fake([
        'https://api.zoom.us/v2/meetings/zoom-meeting-123' => Http::response([
            'id' => 'zoom-meeting-123',
            'start_url' => 'https://zoom.us/s/host-start-link',
            'join_url' => 'https://zoom.us/j/9876543210',
        ]),
    ]);

    Carbon::setTestNow($startUtc->copy()->subSecond());

    $atStart = $this
        ->actingAs($context['mentorUser'])
        ->get(route('mentor.bookings.start-meeting', $booking->id));

    $atStart->assertRedirect(route('mentor.bookings.show', $booking->id));
    $atStart->assertSessionHas('error', sprintf(
        'Meeting access will be enabled at %s on %s.',
        $startLocal->format('g:i A'),
        $startLocal->format('F j, Y')
    ));
});

it('uses the gated student route in confirmation and reminder emails for synced zoom bookings', function () {
    Mail::fake();

    $context = createBookingAccessContext();
    $booking = $context['booking'];
    $presenter = app(BookingMeetingPresenter::class);

    (new SendBookingConfirmationJob($booking->id))->handle($presenter);
    (new SendBookingReminderJob($booking->id, 24))->handle($presenter);

    Mail::assertSent(StudentBookingConfirmationMail::class, function (StudentBookingConfirmationMail $mail) use ($booking) {
        return $mail->bookingDetails['meeting_link'] === route('student.bookings.join-meeting', $booking->id)
            && $mail->bookingDetails['meeting_link_label'] === 'Join Zoom Meeting';
    });

    Mail::assertSent(BookingReminderMail::class, function (BookingReminderMail $mail) use ($booking) {
        return $mail->recipientRole === 'participant'
            && $mail->bookingDetails['meeting_link'] === route('student.bookings.join-meeting', $booking->id)
            && $mail->bookingDetails['meeting_link_label'] === 'Join Zoom Meeting';
    });
});

it('allows invited registered participants to join group meetings using the student join route', function () {
    $context = createBookingAccessContext();
    $invitedStudent = createBookingAccessUser('student');
    $booking = $context['booking'];

    $booking->forceFill([
        'session_type' => '1on3',
        'requested_group_size' => 3,
        'is_group_payer' => true,
        'group_payer_id' => $context['studentUser']->id,
    ])->save();

    DB::table('booking_participants')->insert([
        'booking_id' => $booking->id,
        'user_id' => null,
        'full_name' => $invitedStudent->name,
        'email' => $invitedStudent->email,
        'participant_role' => 'guest',
        'is_primary' => false,
        'invite_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Carbon::setTestNow($context['sessionAtLocal']->copy()->utc()->subSecond());

    $response = $this
        ->actingAs($invitedStudent)
        ->get(route('student.bookings.join-meeting', $booking->id));

    $response->assertRedirect('https://zoom.us/j/9876543210');
});

it('uses the raw zoom link for email-only guests in booking emails', function () {
    Mail::fake();

    $context = createBookingAccessContext();
    $booking = $context['booking'];
    $booking->forceFill([
        'session_type' => '1on3',
        'requested_group_size' => 3,
        'is_group_payer' => true,
        'group_payer_id' => $context['studentUser']->id,
    ])->save();

    DB::table('booking_participants')->insert([
        'booking_id' => $booking->id,
        'user_id' => null,
        'full_name' => 'Guest Student',
        'email' => 'email-only-guest@example.com',
        'participant_role' => 'guest',
        'is_primary' => false,
        'invite_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $presenter = app(BookingMeetingPresenter::class);

    (new SendBookingConfirmationJob($booking->id))->handle($presenter);
    (new SendBookingReminderJob($booking->id, 24))->handle($presenter);

    Mail::assertSent(StudentBookingConfirmationMail::class, function (StudentBookingConfirmationMail $mail) use ($booking) {
        return $mail->hasTo('email-only-guest@example.com')
            && $mail->bookingDetails['meeting_link'] === $booking->meeting_link
            && $mail->bookingDetails['meeting_link_label'] === 'Open Zoom Meeting';
    });

    Mail::assertSent(BookingReminderMail::class, function (BookingReminderMail $mail) use ($booking) {
        return $mail->hasTo('email-only-guest@example.com')
            && $mail->bookingDetails['meeting_link'] === $booking->meeting_link
            && $mail->bookingDetails['meeting_link_label'] === 'Open Zoom Meeting';
    });
});

it('includes mentor program name and email in the student feedback payload', function () {
    $context = createBookingAccessContext();
    $mentorUser = $context['mentorUser'];
    $mentor = $context['mentor'];

    $university = University::query()->create([
        'name' => 'University of Payload',
        'display_name' => 'Payload U',
        'country' => 'US',
        'is_active' => true,
    ]);

    $program = UniversityProgram::query()->create([
        'university_id' => $university->id,
        'program_name' => 'Master of Feedback Systems',
        'program_type' => 'mba',
        'tier' => 'top',
        'is_active' => true,
    ]);

    $mentor->forceFill(['university_program_id' => $program->id])->save();
    $mentorUser->forceFill(['email' => 'mentor.feedback@example.edu'])->save();

    $response = $this
        ->actingAs($context['studentUser'])
        ->get(route('student.bookings.show', $context['booking']->id));

    $response->assertOk();

    $payload = $response->viewData('bookingPageData');
    $selectedBooking = $payload['selectedBooking'];
    $upcomingBooking = collect($payload['upcomingBookings'])->firstWhere('id', $context['booking']->id);

    expect($selectedBooking['mentorProgram'])->toBe('Master of Feedback Systems');
    expect($selectedBooking['mentorEmail'])->toBe('mentor.feedback@example.edu');
    expect($upcomingBooking['mentorProgram'])->toBe('Master of Feedback Systems');
    expect($upcomingBooking['mentorEmail'])->toBe('mentor.feedback@example.edu');
});

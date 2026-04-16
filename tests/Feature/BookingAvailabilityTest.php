<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Jobs\SendBookingConfirmationJob;
use Modules\Bookings\app\Mail\MentorBookingNotificationMail;
use Modules\Bookings\app\Mail\StudentBookingConfirmationMail;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Payments\app\Models\UserCredit;
use Modules\Settings\app\Models\Mentor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(DatabaseTransactions::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('student', 'web');
    Role::findOrCreate('mentor', 'web');
    Role::findOrCreate('admin', 'web');
});

function makeUser(string $emailPrefix): User
{
    return User::factory()->create([
        'email' => $emailPrefix.'-'.Str::uuid().'@example.edu',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
}

function makeService(array $overrides = []): ServiceConfig
{
    return ServiceConfig::query()->create(array_merge([
        'service_name' => 'Tutoring',
        'service_slug' => 'tutoring-'.Str::lower(Str::random(8)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 70,
        'price_1on3_per_person' => 60,
        'price_1on3_total' => 180,
        'price_1on5_per_person' => 50,
        'price_1on5_total' => 250,
        'is_office_hours' => false,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 1,
    ], $overrides));
}

function makeMentor(): Mentor
{
    $mentorUser = makeUser('mentor');
    $mentorUser->assignRole('mentor');

    return Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'grad_school_display' => 'Harvard',
        'status' => 'active',
    ]);
}

function attachMentorService(Mentor $mentor, ServiceConfig $service): void
{
    DB::table('mentor_services')->insert([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'is_active' => true,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createAvailabilitySlot(Mentor $mentor, ServiceConfig $service, string $sessionType, int $daysAhead = 6): int
{
    return DB::table('mentor_availability_slots')->insertGetId([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'slot_date' => now()->addDays($daysAhead)->toDateString(),
        'start_time' => '13:00:00',
        'end_time' => '14:00:00',
        'timezone' => 'America/New_York',
        'session_type' => $sessionType,
        'max_participants' => match ($sessionType) {
            '1on3' => 3,
            '1on5' => 5,
            default => 1,
        },
        'booked_participants_count' => 0,
        'is_booked' => false,
        'is_blocked' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function fundStudent(User $student, int $balance = 5): void
{
    UserCredit::query()->create([
        'user_id' => $student->id,
        'balance' => $balance,
    ]);
}

it('returns only real available slot months days and times', function () {
    $student = makeUser('student');
    $student->assignRole('student');
    $mentor = makeMentor();
    $service = makeService();

    DB::table('mentor_services')->insert([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'is_active' => true,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $openSlotId = DB::table('mentor_availability_slots')->insertGetId([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'slot_date' => now()->addDays(8)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'timezone' => 'America/New_York',
        'session_type' => '1on1',
        'max_participants' => 1,
        'booked_participants_count' => 0,
        'is_booked' => false,
        'is_blocked' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $bookedSlotId = DB::table('mentor_availability_slots')->insertGetId([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'slot_date' => now()->addDays(9)->toDateString(),
        'start_time' => '15:00:00',
        'end_time' => '16:00:00',
        'timezone' => 'America/New_York',
        'session_type' => '1on1',
        'max_participants' => 1,
        'booked_participants_count' => 1,
        'is_booked' => true,
        'is_blocked' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Booking::query()->create([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'mentor_availability_slot_id' => $bookedSlotId,
        'session_type' => '1on1',
        'session_at' => now()->addDays(9)->setTime(15, 0),
        'session_timezone' => 'America/New_York',
        'duration_minutes' => 60,
        'meeting_type' => 'zoom',
        'credits_charged' => 1,
        'amount_charged' => 70,
        'currency' => 'USD',
        'status' => 'confirmed',
        'approval_status' => 'not_required',
    ]);

    $query = [
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
    ];

    $months = $this->actingAs($student)
        ->getJson(route('student.bookings.availability.months', $query))
        ->assertOk()
        ->json('months');

    expect($months)->toHaveCount(1);

    $month = $months[0]['month'];

    $days = $this->actingAs($student)
        ->getJson(route('student.bookings.availability.days', array_merge($query, ['month' => $month])))
        ->assertOk()
        ->json('days');

    expect($days)->toHaveCount(1)
        ->and($days[0]['date'])->toBe(now()->addDays(8)->toDateString());

    $times = $this->actingAs($student)
        ->getJson(route('student.bookings.availability.times', array_merge($query, [
            'date' => now()->addDays(8)->toDateString(),
        ])))
        ->assertOk()
        ->json('times');

    expect($times)->toHaveCount(1)
        ->and($times[0]['slotId'])->toBe($openSlotId);
});

it('creates a booking from a selected availability slot and reserves it', function () {
    $student = makeUser('booking-student');
    $student->assignRole('student');
    UserCredit::query()->create([
        'user_id' => $student->id,
        'balance' => 5,
    ]);

    $mentor = makeMentor();
    $service = makeService([
        'service_slug' => 'program-insights-'.Str::lower(Str::random(5)),
    ]);

    DB::table('mentor_services')->insert([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'is_active' => true,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $slotId = DB::table('mentor_availability_slots')->insertGetId([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'slot_date' => now()->addDays(6)->toDateString(),
        'start_time' => '13:00:00',
        'end_time' => '14:00:00',
        'timezone' => 'America/New_York',
        'session_type' => '1on3',
        'max_participants' => 3,
        'booked_participants_count' => 0,
        'is_booked' => false,
        'is_blocked' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($student)
        ->post(route('student.bookings.store'), [
            'mentor_id' => $mentor->id,
            'service_config_id' => $service->id,
            'session_type' => '1on3',
            'mentor_availability_slot_id' => $slotId,
            'guest_participants' => [
                ['full_name' => 'Guest Two', 'email' => 'guesttwo@example.edu'],
                ['full_name' => 'Guest Three', 'email' => 'guestthree@example.edu'],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('bookings', [
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'mentor_availability_slot_id' => $slotId,
        'session_type' => '1on3',
        'approval_status' => 'pending',
        'is_group_payer' => true,
        'group_payer_id' => $student->id,
    ]);

    $this->assertDatabaseHas('mentor_availability_slots', [
        'id' => $slotId,
        'is_booked' => true,
        'booked_participants_count' => 3,
    ]);
});

it('queues a confirmation job and sends booking emails for 1on1 to student and mentor', function () {
    Queue::fake();

    $student = makeUser('booking-student');
    $student->assignRole('student');
    fundStudent($student);

    $mentor = makeMentor();
    $service = makeService([
        'service_name' => 'Application Review',
        'service_slug' => 'application-review-'.Str::lower(Str::random(5)),
        'price_1on1' => 60,
    ]);

    attachMentorService($mentor, $service);
    $slotId = createAvailabilitySlot($mentor, $service, '1on1');

    $this->actingAs($student)
        ->post(route('student.bookings.store'), [
            'mentor_id' => $mentor->id,
            'service_config_id' => $service->id,
            'session_type' => '1on1',
            'mentor_availability_slot_id' => $slotId,
        ])
        ->assertRedirect();

    $booking = Booking::query()
        ->with(['mentor.user', 'student', 'service', 'participantRecords'])
        ->latest('id')
        ->firstOrFail();

    expect($booking->meeting_link)->not->toBeNull();

    Queue::assertPushed(SendBookingConfirmationJob::class, function (SendBookingConfirmationJob $job) use ($booking) {
        return $job->bookingId === $booking->id;
    });

    Mail::fake();

    (new SendBookingConfirmationJob($booking->id))->handle();

    Mail::assertSent(StudentBookingConfirmationMail::class, function (StudentBookingConfirmationMail $mail) use ($student, $mentor, $booking) {
        return $mail->hasTo($student->email)
            && $mail->bookingDetails['counterpart_name'] === $mentor->user->name
            && $mail->bookingDetails['mentor_name'] === $mentor->user->name
            && !array_key_exists('mentor_email', $mail->bookingDetails)
            && $mail->bookingDetails['meeting_link'] === $booking->meeting_link
            && !str_contains($mail->render(), $mentor->user->email);
    });

    Mail::assertSent(MentorBookingNotificationMail::class, function (MentorBookingNotificationMail $mail) use ($mentor, $student, $booking) {
        return $mail->hasTo($mentor->user->email)
            && $mail->bookingDetails['counterpart_name'] === $student->name
            && $mail->bookingDetails['student_email'] === $student->email
            && $mail->bookingDetails['meeting_link'] === $booking->meeting_link
            && str_contains($mail->render(), $student->email);
    });
});

it('queues a confirmation job and sends booking emails for 1on3 to mentor and all participants without duplicates', function () {
    Queue::fake();

    $student = makeUser('booking-student');
    $student->assignRole('student');
    fundStudent($student);

    $mentor = makeMentor();
    $service = makeService([
        'service_name' => 'Program Insights',
        'service_slug' => 'program-insights-'.Str::lower(Str::random(5)),
    ]);

    attachMentorService($mentor, $service);
    $slotId = createAvailabilitySlot($mentor, $service, '1on3');

    $guestEmailOne = 'guestone@example.edu';
    $guestEmailTwo = 'guesttwo@example.edu';

    $this->actingAs($student)
        ->post(route('student.bookings.store'), [
            'mentor_id' => $mentor->id,
            'service_config_id' => $service->id,
            'session_type' => '1on3',
            'mentor_availability_slot_id' => $slotId,
            'guest_participants' => [
                ['full_name' => 'Guest One', 'email' => $guestEmailOne],
                ['full_name' => 'Guest Two', 'email' => $guestEmailTwo],
            ],
        ])
        ->assertRedirect();

    $booking = Booking::query()
        ->with(['mentor.user', 'student', 'service', 'participantRecords'])
        ->latest('id')
        ->firstOrFail();

    Queue::assertPushed(SendBookingConfirmationJob::class, fn (SendBookingConfirmationJob $job) => $job->bookingId === $booking->id);

    Mail::fake();

    (new SendBookingConfirmationJob($booking->id))->handle();

    Mail::assertSent(MentorBookingNotificationMail::class, 1);
    Mail::assertSent(StudentBookingConfirmationMail::class, 3);

    Mail::assertSent(StudentBookingConfirmationMail::class, fn (StudentBookingConfirmationMail $mail) => $mail->hasTo($student->email));
    Mail::assertSent(StudentBookingConfirmationMail::class, fn (StudentBookingConfirmationMail $mail) => $mail->hasTo($guestEmailOne));
    Mail::assertSent(StudentBookingConfirmationMail::class, fn (StudentBookingConfirmationMail $mail) => $mail->hasTo($guestEmailTwo));
});

it('queues a confirmation job and sends booking emails for 1on5 to mentor and all participants', function () {
    Queue::fake();

    $student = makeUser('booking-student');
    $student->assignRole('student');
    fundStudent($student);

    $mentor = makeMentor();
    $service = makeService([
        'service_name' => 'Interview Prep',
        'service_slug' => 'interview-prep-'.Str::lower(Str::random(5)),
    ]);

    attachMentorService($mentor, $service);
    $slotId = createAvailabilitySlot($mentor, $service, '1on5');

    $guestParticipants = [
        ['full_name' => 'Guest One', 'email' => 'guestone@example.edu'],
        ['full_name' => 'Guest Two', 'email' => 'guesttwo@example.edu'],
        ['full_name' => 'Guest Three', 'email' => 'guestthree@example.edu'],
        ['full_name' => 'Guest Four', 'email' => 'guestfour@example.edu'],
    ];

    $this->actingAs($student)
        ->post(route('student.bookings.store'), [
            'mentor_id' => $mentor->id,
            'service_config_id' => $service->id,
            'session_type' => '1on5',
            'mentor_availability_slot_id' => $slotId,
            'guest_participants' => $guestParticipants,
        ])
        ->assertRedirect();

    $booking = Booking::query()
        ->with(['mentor.user', 'student', 'service', 'participantRecords'])
        ->latest('id')
        ->firstOrFail();

    Queue::assertPushed(SendBookingConfirmationJob::class, fn (SendBookingConfirmationJob $job) => $job->bookingId === $booking->id);

    Mail::fake();

    (new SendBookingConfirmationJob($booking->id))->handle();

    Mail::assertSent(MentorBookingNotificationMail::class, 1);
    Mail::assertSent(StudentBookingConfirmationMail::class, 5);

    foreach ($guestParticipants as $participant) {
        Mail::assertSent(StudentBookingConfirmationMail::class, fn (StudentBookingConfirmationMail $mail) => $mail->hasTo($participant['email']));
    }
});

it('does not send standard booking emails for office hours', function () {
    Queue::fake();

    $student = makeUser('office-hours-booking-student');
    $student->assignRole('student');
    fundStudent($student);

    $mentor = makeMentor();
    $officeHoursService = makeService([
        'service_name' => 'Office Hours',
        'service_slug' => 'office-hours-'.Str::lower(Str::random(5)),
        'is_office_hours' => true,
        'price_1on1' => null,
        'price_1on3_per_person' => null,
        'price_1on3_total' => null,
        'price_1on5_per_person' => null,
        'price_1on5_total' => null,
        'office_hours_subscription_price' => 200,
    ]);
    $supportService = makeService([
        'service_name' => 'Interview Prep',
        'service_slug' => 'interview-prep-'.Str::lower(Str::random(5)),
    ]);

    DB::table('mentor_services')->insert([
        [
            'mentor_id' => $mentor->id,
            'service_config_id' => $officeHoursService->id,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'mentor_id' => $mentor->id,
            'service_config_id' => $supportService->id,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $scheduleId = DB::table('office_hour_schedules')->insertGetId([
        'mentor_id' => $mentor->id,
        'day_of_week' => 'tue',
        'start_time' => '17:00:00',
        'timezone' => 'America/New_York',
        'frequency' => 'weekly',
        'max_spots' => 3,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $sessionId = DB::table('office_hour_sessions')->insertGetId([
        'schedule_id' => $scheduleId,
        'current_service_id' => $supportService->id,
        'session_date' => now()->addDays(7)->toDateString(),
        'start_time' => '17:00:00',
        'timezone' => 'America/New_York',
        'current_occupancy' => 0,
        'max_spots' => 3,
        'is_full' => false,
        'service_locked' => false,
        'status' => 'upcoming',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($student)
        ->post(route('student.bookings.store'), [
            'mentor_id' => $mentor->id,
            'service_config_id' => $officeHoursService->id,
            'session_type' => 'office_hours',
            'office_hour_session_id' => $sessionId,
        ])
        ->assertRedirect();

    $booking = Booking::query()->latest('id')->firstOrFail();

    Queue::assertPushed(SendBookingConfirmationJob::class, fn (SendBookingConfirmationJob $job) => $job->bookingId === $booking->id);

    Mail::fake();

    (new SendBookingConfirmationJob($booking->id))->handle();

    Mail::assertNothingSent();
});

it('renders dynamic office hours data from upcoming sessions', function () {
    $student = makeUser('office-hours-student');
    $student->assignRole('student');
    $mentor = makeMentor();
    $officeHoursService = makeService([
        'service_name' => 'Office Hours',
        'service_slug' => 'office_hours_'.Str::lower(Str::random(6)),
        'is_office_hours' => true,
        'price_1on1' => null,
        'price_1on3_per_person' => null,
        'price_1on3_total' => null,
        'price_1on5_per_person' => null,
        'price_1on5_total' => null,
        'office_hours_subscription_price' => 200,
    ]);
    $supportService = makeService([
        'service_name' => 'Interview Prep',
        'service_slug' => 'interview_prep_'.Str::lower(Str::random(6)),
    ]);

    DB::table('mentor_services')->insert([
        [
            'mentor_id' => $mentor->id,
            'service_config_id' => $officeHoursService->id,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'mentor_id' => $mentor->id,
            'service_config_id' => $supportService->id,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $scheduleId = DB::table('office_hour_schedules')->insertGetId([
        'mentor_id' => $mentor->id,
        'day_of_week' => 'tue',
        'start_time' => '17:00:00',
        'timezone' => 'America/New_York',
        'frequency' => 'weekly',
        'max_spots' => 3,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('office_hour_sessions')->insert([
        'schedule_id' => $scheduleId,
        'current_service_id' => $supportService->id,
        'session_date' => now()->addDays(7)->toDateString(),
        'start_time' => '17:00:00',
        'timezone' => 'America/New_York',
        'current_occupancy' => 2,
        'max_spots' => 3,
        'is_full' => false,
        'service_locked' => true,
        'status' => 'upcoming',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.office-hours'))
        ->assertOk()
        ->assertViewHas('officeHoursData', function (array $officeHoursData) use ($mentor) {
            return collect($officeHoursData)->contains(function (array $row) use ($mentor) {
                return (int) $row['id'] === (int) $mentor->id
                    && (int) $row['spotsFilled'] === 2
                    && (int) $row['maxSpots'] === 3
                    && $row['isBookable'] === true;
            });
        });
});

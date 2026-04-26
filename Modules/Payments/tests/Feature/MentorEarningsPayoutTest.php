<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Database\Seeders\ServiceConfigSeeder;
use Modules\Bookings\app\Models\BookingMeetingEvent;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\Bookings\app\Services\BookingAttendanceResolver;
use Modules\Bookings\app\Services\BookingOutcomeService;
use Modules\Bookings\app\Services\BookingService;
use Modules\OfficeHours\app\Models\OfficeHourSchedule;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\Payments\app\Models\BookingPayment;
use Modules\Payments\app\Models\BookingRefund;
use Modules\Payments\app\Models\MentorPayout;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Payments\app\Services\CreditService;
use Modules\Payments\app\Services\MentorPayoutCalculator;
use Modules\Payments\app\Services\MentorPayoutService;
use Modules\Payments\app\Services\StripeWebhookService;
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

    config()->set('services.stripe.secret_key', 'sk_test_fake');
    config()->set('services.stripe.api_base', 'https://api.stripe.com/v1');
});

function createPayoutUser(string $role): User
{
    $user = User::factory()->create([
        'email' => Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

function createPaidBookingContext(array $mentorOverrides = []): array
{
    $student = createPayoutUser('student');
    $mentorUser = createPayoutUser('mentor');
    $mentor = Mentor::query()->create(array_merge([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
        'stripe_account_id' => null,
        'payouts_enabled' => false,
        'stripe_onboarding_complete' => false,
    ], $mentorOverrides));

    $service = ServiceConfig::query()->create([
        'service_name' => 'Career Coaching '.Str::lower(Str::random(5)),
        'service_slug' => 'career_coaching_'.Str::lower(Str::random(6)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 120,
        'platform_fee_1on1' => 36,
        'mentor_payout_1on1' => 84,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 0,
    ]);

    DB::table('mentor_services')->insert([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'is_active' => true,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $slot = MentorAvailabilitySlot::query()->create([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'slot_date' => now()->addDays(4)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'timezone' => 'UTC',
        'session_type' => '1on1',
        'max_participants' => 1,
        'booked_participants_count' => 0,
        'is_booked' => false,
        'is_blocked' => false,
        'is_active' => true,
    ]);

    $payment = BookingPayment::query()->create([
        'user_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'mentor_availability_slot_id' => $slot->id,
        'session_type' => '1on1',
        'meeting_type' => 'zoom',
        'amount' => 120,
        'currency' => 'USD',
        'request_payload' => [
            'mentor_id' => $mentor->id,
            'service_config_id' => $service->id,
            'session_type' => '1on1',
            'mentor_availability_slot_id' => $slot->id,
            'meeting_type' => 'zoom',
            'guest_participants' => [],
            'portal_context' => 'student',
        ],
        'status' => 'initiated',
    ]);

    return compact('student', 'mentorUser', 'mentor', 'service', 'slot', 'payment');
}

function checkoutCompletedPayload(BookingPayment $payment, string $eventId = 'evt_checkout_test'): array
{
    return [
        'id' => $eventId,
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_'.Str::lower(Str::random(8)),
                'payment_status' => 'paid',
                'payment_intent' => 'pi_'.Str::lower(Str::random(8)),
                'metadata' => [
                    'payment_type' => 'booking',
                    'booking_payment_id' => (string) $payment->id,
                ],
            ],
        ],
    ];
}

function createOfficeHoursPayoutContext(array $mentorOverrides = []): array
{
    $mentorUser = createPayoutUser('mentor');
    $mentor = Mentor::query()->create(array_merge([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
        'stripe_account_id' => null,
        'payouts_enabled' => false,
        'stripe_onboarding_complete' => false,
    ], $mentorOverrides));

    $service = ServiceConfig::query()->create([
        'service_name' => 'Office Hours',
        'service_slug' => 'office_hours',
        'duration_minutes' => 45,
        'is_active' => true,
        'is_office_hours' => true,
        'office_hours_subscription_price' => 200,
        'office_hours_mentor_payout_per_attendee' => 15,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 0,
    ]);

    DB::table('mentor_services')->insert([
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'is_active' => true,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $schedule = OfficeHourSchedule::query()->create([
        'mentor_id' => $mentor->id,
        'current_service_id' => $service->id,
        'day_of_week' => 'mon',
        'start_time' => '10:00:00',
        'timezone' => 'UTC',
        'frequency' => 'weekly',
        'max_spots' => 3,
        'is_active' => true,
    ]);

    $session = OfficeHourSession::query()->create([
        'schedule_id' => $schedule->id,
        'current_service_id' => $service->id,
        'session_date' => now()->addDays(4)->toDateString(),
        'start_time' => '10:00:00',
        'timezone' => 'UTC',
        'current_occupancy' => 0,
        'max_spots' => 3,
        'is_full' => false,
        'service_locked' => false,
        'status' => 'upcoming',
    ]);

    return compact('mentorUser', 'mentor', 'service', 'schedule', 'session');
}

function bookOfficeHoursAttendee(array $context): \Modules\Bookings\app\Models\Booking
{
    $student = createPayoutUser('student');
    app(CreditService::class)->purchase($student, 5, 'pi_credit_'.Str::lower(Str::random(8)), 'evt_credit_'.Str::lower(Str::random(8)));

    return app(BookingService::class)->createBooking($student, [
        'mentor_id' => $context['mentor']->id,
        'service_config_id' => $context['service']->id,
        'session_type' => 'office_hours',
        'office_hour_session_id' => $context['session']->id,
        'meeting_type' => 'zoom',
        'guest_participants' => [],
    ]);
}

it('records a pending release payout ledger entry when a paid booking webhook is processed', function () {
    $context = createPaidBookingContext();

    app(StripeWebhookService::class)->process(
        checkoutCompletedPayload($context['payment'], 'evt_booking_paid_1')
    );

    $payment = $context['payment']->fresh();

    expect($payment->status)->toBe('booking_created')
        ->and($payment->booking_id)->not->toBeNull();

    $this->assertDatabaseHas('mentor_payouts', [
        'booking_id' => $payment->booking_id,
        'booking_payment_id' => $payment->id,
        'mentor_id' => $context['mentor']->id,
        'student_id' => $context['student']->id,
        'status' => MentorPayout::STATUS_PENDING_RELEASE,
        'gross_amount' => 120.00,
        'mentor_share_amount' => 84.00,
        'platform_fee_amount' => 36.00,
        'amount' => 84.00,
        'currency' => 'USD',
    ]);
});

it('calculates every configured diagram split exactly', function () {
    $student = createPayoutUser('student');
    $mentorUser = createPayoutUser('mentor');
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    $rows = [
        ['tutoring', 'Tutoring', '1on1', 70.00, 43.00, 27.00],
        ['tutoring', 'Tutoring', '1on3', 188.97, 98.97, 90.00],
        ['tutoring', 'Tutoring', '1on5', 279.95, 114.95, 165.00],
        ['program_insights', 'Program Insights', '1on1', 65.00, 42.00, 23.00],
        ['program_insights', 'Program Insights', '1on3', 175.47, 92.47, 83.00],
        ['program_insights', 'Program Insights', '1on5', 259.95, 113.95, 146.00],
        ['interview_prep', 'Interview Prep', '1on1', 65.00, 42.00, 23.00],
        ['interview_prep', 'Interview Prep', '1on3', 175.47, 92.47, 83.00],
        ['interview_prep', 'Interview Prep', '1on5', 259.95, 113.95, 146.00],
        ['application_review', 'Application Review', '1on1', 60.00, 37.00, 23.00],
        ['gap_year_planning', 'Gap Year Planning', '1on1', 50.00, 31.00, 19.00],
    ];

    foreach ($rows as [$slug, $name, $sessionType, $gross, $mentorShare, $platformFee]) {
        $service = ServiceConfig::query()->firstOrCreate(
            ['service_slug' => $slug],
            [
                'service_name' => $name,
                'duration_minutes' => 60,
                'is_active' => true,
                'price_1on1' => $sessionType === '1on1' ? $gross : null,
                'sort_order' => 0,
            ]
        );

        $service->forceFill(match ($sessionType) {
            '1on3' => [
                'price_1on3_total' => $gross,
                'platform_fee_1on3' => $platformFee,
                'mentor_payout_1on3' => $mentorShare,
            ],
            '1on5' => [
                'price_1on5_total' => $gross,
                'platform_fee_1on5' => $platformFee,
                'mentor_payout_1on5' => $mentorShare,
            ],
            default => [
                'price_1on1' => $gross,
                'platform_fee_1on1' => $platformFee,
                'mentor_payout_1on1' => $mentorShare,
            ],
        })->save();

        $booking = Booking::query()->create([
            'student_id' => $student->id,
            'mentor_id' => $mentor->id,
            'service_config_id' => $service->id,
            'session_type' => $sessionType,
            'session_at' => now()->addDays(4),
            'session_timezone' => 'UTC',
            'duration_minutes' => 60,
            'meeting_type' => 'zoom',
            'status' => 'confirmed',
            'approval_status' => 'not_required',
            'credits_charged' => 0,
            'amount_charged' => $gross,
            'currency' => 'USD',
            'pricing_snapshot' => [],
        ]);

        $payment = BookingPayment::query()->create([
            'user_id' => $student->id,
            'mentor_id' => $mentor->id,
            'service_config_id' => $service->id,
            'booking_id' => $booking->id,
            'session_type' => $sessionType,
            'meeting_type' => 'zoom',
            'amount' => $gross,
            'currency' => 'USD',
            'request_payload' => [],
            'status' => 'booking_created',
        ]);

        $calculation = app(MentorPayoutCalculator::class)->forBooking($booking, $payment);

        expect($calculation['gross_amount'])->toBe($gross)
            ->and($calculation['mentor_share_amount'])->toBe($mentorShare)
            ->and($calculation['platform_fee_amount'])->toBe($platformFee)
            ->and($calculation['calculation_rule']['type'])->toBe('database_service_split');
    }
});

it('seeds diagram service prices and group totals exactly', function () {
    $this->seed(ServiceConfigSeeder::class);

    $expected = [
        'tutoring' => [70.00, 62.99, 188.97, 55.99, 279.95],
        'program_insights' => [65.00, 58.49, 175.47, 51.99, 259.95],
        'interview_prep' => [65.00, 58.49, 175.47, 51.99, 259.95],
        'application_review' => [60.00, null, null, null, null],
        'gap_year_planning' => [50.00, null, null, null, null],
    ];

    foreach ($expected as $slug => [$oneOnOne, $threePerPerson, $threeTotal, $fivePerPerson, $fiveTotal]) {
        $service = ServiceConfig::query()->where('service_slug', $slug)->firstOrFail();

        expect($service->price_1on1 !== null ? (float) $service->price_1on1 : null)->toBe($oneOnOne)
            ->and($service->price_1on3_per_person !== null ? (float) $service->price_1on3_per_person : null)->toBe($threePerPerson)
            ->and($service->price_1on3_total !== null ? (float) $service->price_1on3_total : null)->toBe($threeTotal)
            ->and($service->price_1on5_per_person !== null ? (float) $service->price_1on5_per_person : null)->toBe($fivePerPerson)
            ->and($service->price_1on5_total !== null ? (float) $service->price_1on5_total : null)->toBe($fiveTotal);
    }

    $tutoring = ServiceConfig::query()->where('service_slug', 'tutoring')->firstOrFail();

    expect((float) $tutoring->platform_fee_1on1)->toBe(27.0)
        ->and((float) $tutoring->mentor_payout_1on1)->toBe(43.0)
        ->and((float) $tutoring->platform_fee_1on3)->toBe(90.0)
        ->and((float) $tutoring->mentor_payout_1on3)->toBe(98.97)
        ->and((float) $tutoring->platform_fee_1on5)->toBe(165.0)
        ->and((float) $tutoring->mentor_payout_1on5)->toBe(114.95);
});

it('uses database service split rules for paid booking payouts', function () {
    $context = createPaidBookingContext();

    $context['service']->forceFill([
        'service_name' => 'Tutoring',
        'service_slug' => 'tutoring',
        'price_1on1' => 75,
        'platform_fee_1on1' => 30,
        'mentor_payout_1on1' => 45,
    ])->save();

    $context['payment']->forceFill([
        'amount' => 75,
    ])->save();

    app(StripeWebhookService::class)->process(
        checkoutCompletedPayload($context['payment'], 'evt_booking_database_split')
    );

    $payment = $context['payment']->fresh();
    $payout = MentorPayout::query()->where('booking_id', $payment->booking_id)->firstOrFail();

    expect((float) $payout->gross_amount)->toBe(75.0)
        ->and((float) $payout->mentor_share_amount)->toBe(45.0)
        ->and((float) $payout->platform_fee_amount)->toBe(30.0)
        ->and($payout->calculation_rule['type'])->toBe('database_service_split')
        ->and($payout->calculation_rule['service_slug'])->toBe('tutoring');
});

it('fails paid payout calculation when admin split fields are missing', function () {
    $context = createPaidBookingContext();

    $context['service']->forceFill([
        'service_name' => 'Unconfigured Paid Service',
        'service_slug' => 'unconfigured_paid_service',
        'price_1on1' => 65,
        'platform_fee_1on1' => null,
        'mentor_payout_1on1' => null,
    ])->save();

    $context['payment']->forceFill([
        'amount' => 65,
    ])->save();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Service payout split is not configured in admin pricing.');

    app(StripeWebhookService::class)->process(
        checkoutCompletedPayload($context['payment'], 'evt_booking_missing_split')
    );
});

it('creates a pending fifteen dollar office-hours payout when a student uses one credit', function () {
    $context = createOfficeHoursPayoutContext();

    $booking = bookOfficeHoursAttendee($context);
    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($booking->credits_charged)->toBe(1)
        ->and((float) $booking->amount_charged)->toBe(0.0)
        ->and($payout->status)->toBe(MentorPayout::STATUS_PENDING_RELEASE)
        ->and((float) $payout->mentor_share_amount)->toBe(15.0)
        ->and($payout->booking_payment_id)->toBeNull()
        ->and($payout->calculation_rule['type'])->toBe('office_hours_per_attendee')
        ->and((float) $payout->calculation_rule['mentor_amount_per_attendee'])->toBe(15.0)
        ->and($payout->calculation_rule['attendee_number'])->toBe(1);
});

it('uses database office-hours mentor payout per attendee when present', function () {
    $context = createOfficeHoursPayoutContext();
    $context['service']->forceFill([
        'office_hours_mentor_payout_per_attendee' => 22,
    ])->save();

    $booking = bookOfficeHoursAttendee($context);
    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();

    expect((float) $payout->mentor_share_amount)->toBe(22.0)
        ->and((float) $payout->calculation_rule['mentor_amount_per_attendee'])->toBe(22.0);
});

it('creates one fifteen dollar office-hours payout per attendee and caps the session at three', function () {
    $context = createOfficeHoursPayoutContext();

    bookOfficeHoursAttendee($context);
    bookOfficeHoursAttendee($context);
    bookOfficeHoursAttendee($context);

    expect((float) MentorPayout::query()->sum('mentor_share_amount'))->toBe(45.0)
        ->and(MentorPayout::query()->count())->toBe(3)
        ->and($context['session']->fresh()->current_occupancy)->toBe(3)
        ->and($context['session']->fresh()->is_full)->toBeTrue();

    $this->expectException(\Modules\Bookings\app\Exceptions\BookingException::class);
    $this->expectExceptionMessage('This office-hours session is no longer bookable.');

    bookOfficeHoursAttendee($context);
});

it('transfers a fifteen dollar office-hours payout after completion', function () {
    Http::fake([
        'https://api.stripe.com/v1/transfers' => Http::response([
            'id' => 'tr_office_hours_123',
            'balance_transaction' => 'txn_office_hours_123',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createOfficeHoursPayoutContext([
        'stripe_account_id' => 'acct_office_hours_ready_123',
        'payouts_enabled' => true,
        'stripe_onboarding_complete' => true,
    ]);

    $booking = bookOfficeHoursAttendee($context);
    $admin = createPayoutUser('admin');

    app(BookingOutcomeService::class)->update($booking, $admin, [
        'session_outcome' => 'completed',
    ]);

    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($payout->status)->toBe(MentorPayout::STATUS_TRANSFERRED)
        ->and((float) $payout->mentor_share_amount)->toBe(15.0)
        ->and($payout->stripe_transfer_id)->toBe('tr_office_hours_123');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/transfers'
        && str_contains($request->body(), 'amount=1500')
        && str_contains($request->body(), 'destination=acct_office_hours_ready_123'));
});

it('creates exactly one transfer when a payout-enabled mentor booking is completed twice', function () {
    Http::fake([
        'https://api.stripe.com/v1/transfers' => Http::response([
            'id' => 'tr_test_123',
            'balance_transaction' => 'txn_test_123',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createPaidBookingContext([
        'stripe_account_id' => 'acct_transfer_ready_123',
        'payouts_enabled' => true,
        'stripe_onboarding_complete' => true,
    ]);

    app(StripeWebhookService::class)->process(
        checkoutCompletedPayload($context['payment'], 'evt_booking_paid_2')
    );

    $admin = createPayoutUser('admin');
    $booking = $context['payment']->fresh()->booking()->firstOrFail();

    app(BookingOutcomeService::class)->update($booking, $admin, [
        'session_outcome' => 'completed',
    ]);

    app(BookingOutcomeService::class)->update($booking->fresh(), $admin, [
        'session_outcome' => 'completed',
    ]);

    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($payout->status)->toBe(MentorPayout::STATUS_TRANSFERRED)
        ->and($payout->stripe_transfer_id)->toBe('tr_test_123')
        ->and($payout->transferred_at)->not->toBeNull();

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/transfers'
        && str_contains($request->body(), 'destination=acct_transfer_ready_123')
        && str_contains($request->body(), 'transfer_group=booking_'.$booking->id));
});

it('releases payout when zoom attendance marks a paid booking attended', function () {
    Http::fake([
        'https://api.stripe.com/v1/transfers' => Http::response([
            'id' => 'tr_zoom_attended_123',
            'balance_transaction' => 'txn_zoom_attended_123',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createPaidBookingContext([
        'stripe_account_id' => 'acct_zoom_attended_123',
        'payouts_enabled' => true,
        'stripe_onboarding_complete' => true,
    ]);

    app(StripeWebhookService::class)->process(
        checkoutCompletedPayload($context['payment'], 'evt_booking_paid_zoom')
    );

    $booking = $context['payment']->fresh()->booking()->firstOrFail();
    $startedAt = now()->subMinutes(20);
    $hostJoinedAt = now()->subMinutes(19);
    $attendeeJoinedAt = now()->subMinutes(18);
    $endedAt = now()->subMinutes(10);

    foreach ([
        ['event_type' => 'meeting.started', 'meeting_started_at' => $startedAt, 'occurred_at' => $startedAt],
        ['event_type' => 'meeting.participant_joined', 'host_joined_at' => $hostJoinedAt, 'occurred_at' => $hostJoinedAt],
        ['event_type' => 'meeting.participant_joined', 'first_participant_joined_at' => $attendeeJoinedAt, 'occurred_at' => $attendeeJoinedAt],
        ['event_type' => 'meeting.ended', 'meeting_ended_at' => $endedAt, 'occurred_at' => $endedAt],
    ] as $index => $event) {
        BookingMeetingEvent::query()->create(array_merge([
            'booking_id' => $booking->id,
            'provider' => 'zoom',
            'provider_meeting_id' => 'zoom-attended-payout',
            'event_id' => 'zoom_attended_payout_'.$index,
            'received_at' => now(),
            'is_verified' => true,
            'processed' => true,
            'payload_hash' => hash('sha256', 'zoom_attended_payout_'.$index),
            'payload' => [],
        ], $event));
    }

    $booking = app(BookingAttendanceResolver::class)->refresh($booking);
    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($booking->status)->toBe('completed')
        ->and($booking->attendance_status)->toBe('attended')
        ->and($payout->status)->toBe(MentorPayout::STATUS_TRANSFERRED)
        ->and($payout->stripe_transfer_id)->toBe('tr_zoom_attended_123');
});

it('keeps payouts ready without transferring when mentor onboarding is incomplete', function () {
    Http::fake();
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createPaidBookingContext([
        'stripe_account_id' => 'acct_not_ready_123',
        'payouts_enabled' => false,
    ]);

    app(StripeWebhookService::class)->process(
        checkoutCompletedPayload($context['payment'], 'evt_booking_paid_3')
    );

    $admin = createPayoutUser('admin');
    $booking = $context['payment']->fresh()->booking()->firstOrFail();

    app(BookingOutcomeService::class)->update($booking, $admin, [
        'session_outcome' => 'completed',
    ]);

    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($payout->status)->toBe(MentorPayout::STATUS_READY)
        ->and($payout->eligible_at)->not->toBeNull()
        ->and($payout->stripe_transfer_id)->toBeNull();

    Http::assertNothingSent();
});

it('marks the payout as failed when stripe transfer creation fails', function () {
    Http::fake([
        'https://api.stripe.com/v1/transfers' => Http::response([
            'error' => [
                'message' => 'Insufficient platform balance.',
            ],
        ], 400),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createPaidBookingContext([
        'stripe_account_id' => 'acct_transfer_fail_123',
        'payouts_enabled' => true,
        'stripe_onboarding_complete' => true,
    ]);

    app(StripeWebhookService::class)->process(
        checkoutCompletedPayload($context['payment'], 'evt_booking_paid_4')
    );

    $admin = createPayoutUser('admin');
    $booking = $context['payment']->fresh()->booking()->firstOrFail();

    app(BookingOutcomeService::class)->update($booking, $admin, [
        'session_outcome' => 'completed',
    ]);

    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($payout->status)->toBe(MentorPayout::STATUS_FAILED)
        ->and($payout->attempt_count)->toBe(1)
        ->and($payout->failure_reason)->toContain('Insufficient platform balance');
});

it('retries a ready payout successfully after mentor onboarding completes', function () {
    Http::fake([
        'https://api.stripe.com/v1/transfers' => Http::response([
            'id' => 'tr_retry_123',
            'balance_transaction' => 'txn_retry_123',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createPaidBookingContext([
        'stripe_account_id' => 'acct_retry_ready_123',
        'payouts_enabled' => false,
    ]);

    app(StripeWebhookService::class)->process(
        checkoutCompletedPayload($context['payment'], 'evt_booking_paid_5')
    );

    $admin = createPayoutUser('admin');
    $booking = $context['payment']->fresh()->booking()->firstOrFail();

    app(BookingOutcomeService::class)->update($booking, $admin, [
        'session_outcome' => 'completed',
    ]);

    $context['mentor']->forceFill([
        'payouts_enabled' => true,
        'stripe_onboarding_complete' => true,
    ])->save();

    $processed = app(MentorPayoutService::class)->retryEligiblePayouts();
    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($processed)->toBe(1)
        ->and($payout->status)->toBe(MentorPayout::STATUS_TRANSFERRED)
        ->and($payout->stripe_transfer_id)->toBe('tr_retry_123');
});

it('reverses pending payout rows when a paid booking is cancelled before completion', function () {
    Http::fake([
        'https://api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_cancel_pending_123',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createPaidBookingContext([
        'stripe_account_id' => 'acct_cancel_123',
        'payouts_enabled' => true,
        'stripe_onboarding_complete' => true,
    ]);

    app(StripeWebhookService::class)->process(
        checkoutCompletedPayload($context['payment'], 'evt_booking_paid_6')
    );

    $booking = $context['payment']->fresh()->booking()->firstOrFail();

    app(\Modules\Bookings\app\Services\BookingService::class)->cancelBooking(
        $booking,
        $context['student'],
        'Changed plans'
    );

    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();
    $refund = BookingRefund::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($payout->status)->toBe(MentorPayout::STATUS_REVERSED)
        ->and($payout->stripe_transfer_id)->toBeNull()
        ->and($refund->status)->toBe(BookingRefund::STATUS_SUCCEEDED)
        ->and($refund->stripe_refund_id)->toBe('re_cancel_pending_123');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/refunds');
});

<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Bookings\app\Models\BookingMeetingEvent;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\Bookings\app\Services\BookingAttendanceResolver;
use Modules\Bookings\app\Services\BookingOutcomeService;
use Modules\Payments\app\Models\BookingPayment;
use Modules\Payments\app\Models\BookingRefund;
use Modules\Payments\app\Models\MentorPayout;
use Modules\Payments\app\Models\ServiceConfig;
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
    config()->set('payments.mentor_payout_percent_default', 70);
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

<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\Bookings\app\Services\BookingService;
use Modules\Payments\app\Models\BookingPayment;
use Modules\Payments\app\Models\BookingRefund;
use Modules\Payments\app\Models\MentorPayout;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Payments\app\Services\CreditService;
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

function createRefundUser(string $role): User
{
    $user = User::factory()->create([
        'email' => Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

function createRefundBookingContext(float $price = 120, int $credits = 0): array
{
    $student = createRefundUser('student');
    $mentorUser = createRefundUser('mentor');
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
        'stripe_account_id' => 'acct_refund_ready_123',
        'payouts_enabled' => true,
        'stripe_onboarding_complete' => true,
    ]);

    $service = ServiceConfig::query()->create([
        'service_name' => 'Refund Coaching '.Str::lower(Str::random(5)),
        'service_slug' => 'refund_coaching_'.Str::lower(Str::random(6)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => $price,
        'platform_fee_1on1' => $price > 0 ? round($price * 0.3, 2) : null,
        'mentor_payout_1on1' => $price > 0 ? round($price * 0.7, 2) : null,
        'credit_cost_1on1' => $credits,
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

    return compact('student', 'mentorUser', 'mentor', 'service', 'slot');
}

function createRefundPaidPayment(array $context, string $paymentIntent = 'pi_refund_test_123'): BookingPayment
{
    $payment = BookingPayment::query()->create([
        'user_id' => $context['student']->id,
        'mentor_id' => $context['mentor']->id,
        'service_config_id' => $context['service']->id,
        'mentor_availability_slot_id' => $context['slot']->id,
        'session_type' => '1on1',
        'meeting_type' => 'zoom',
        'amount' => 120,
        'currency' => 'USD',
        'request_payload' => [
            'mentor_id' => $context['mentor']->id,
            'service_config_id' => $context['service']->id,
            'session_type' => '1on1',
            'mentor_availability_slot_id' => $context['slot']->id,
            'meeting_type' => 'zoom',
            'guest_participants' => [],
            'portal_context' => 'student',
        ],
        'status' => 'initiated',
    ]);

    app(StripeWebhookService::class)->process([
        'id' => 'evt_refund_checkout_'.Str::lower(Str::random(8)),
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_refund_'.Str::lower(Str::random(8)),
                'payment_status' => 'paid',
                'payment_intent' => $paymentIntent,
                'metadata' => [
                    'payment_type' => 'booking',
                    'booking_payment_id' => (string) $payment->id,
                ],
            ],
        ],
    ]);

    return $payment->fresh();
}

it('automatically refunds credits when a credit booking is cancelled', function () {
    $context = createRefundBookingContext(price: 0, credits: 1);
    $context['service']->forceFill([
        'is_office_hours' => true,
        'office_hours_subscription_price' => 200,
        'office_hours_mentor_payout_per_attendee' => 15,
    ])->save();

    $scheduleId = DB::table('office_hour_schedules')->insertGetId([
        'mentor_id' => $context['mentor']->id,
        'day_of_week' => 'tue',
        'start_time' => '10:00:00',
        'timezone' => 'UTC',
        'frequency' => 'weekly',
        'max_spots' => 3,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $sessionId = DB::table('office_hour_sessions')->insertGetId([
        'schedule_id' => $scheduleId,
        'current_service_id' => $context['service']->id,
        'session_date' => now()->addDays(4)->toDateString(),
        'start_time' => '10:00:00',
        'timezone' => 'UTC',
        'max_spots' => 3,
        'current_occupancy' => 0,
        'is_full' => false,
        'status' => 'upcoming',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(CreditService::class)->purchase($context['student'], 1, 'pi_credit_seed', 'evt_credit_seed');

    $booking = app(BookingService::class)->createBooking($context['student'], [
        'mentor_id' => $context['mentor']->id,
        'service_config_id' => $context['service']->id,
        'session_type' => 'office_hours',
        'office_hour_session_id' => $sessionId,
        'meeting_type' => 'zoom',
        'guest_participants' => [],
    ]);

    expect(app(CreditService::class)->getBalance($context['student']))->toBe(0);

    app(BookingService::class)->cancelBooking($booking, $context['student'], 'Changed plans');
    app(\Modules\Payments\app\Services\BookingRefundService::class)->refundCancelledBooking($booking->fresh());

    expect(app(CreditService::class)->getBalance($context['student']))->toBe(1);

    $this->assertDatabaseHas('booking_refunds', [
        'booking_id' => $booking->id,
        'type' => BookingRefund::TYPE_CREDITS,
        'status' => BookingRefund::STATUS_SUCCEEDED,
        'credits' => 1,
    ]);

    app(\Modules\Payments\app\Services\BookingRefundService::class)->refundCancelledBooking($booking->fresh());

    expect(app(CreditService::class)->getBalance($context['student']))->toBe(1);
});

it('automatically refunds a paid booking before mentor transfer', function () {
    Http::fake([
        'https://api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_before_transfer_123',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createRefundBookingContext();
    $payment = createRefundPaidPayment($context, 'pi_before_transfer_123');
    $booking = Booking::query()->findOrFail($payment->booking_id);

    app(BookingService::class)->cancelBooking($booking, $context['student'], 'Changed plans');

    $refund = BookingRefund::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($booking->fresh()->status)->toBe('cancelled')
        ->and($refund->status)->toBe(BookingRefund::STATUS_SUCCEEDED)
        ->and($refund->stripe_refund_id)->toBe('re_before_transfer_123');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/refunds'
        && str_contains($request->body(), 'payment_intent=pi_before_transfer_123')
        && str_contains($request->body(), 'amount=12000'));
});

it('reverses a transferred mentor payout before refunding a paid booking', function () {
    Http::fake([
        'https://api.stripe.com/v1/transfers/tr_refund_transfer_123/reversals' => Http::response([
            'id' => 'trr_refund_transfer_123',
        ], 200),
        'https://api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_after_transfer_123',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createRefundBookingContext();
    $payment = createRefundPaidPayment($context, 'pi_after_transfer_123');
    $booking = Booking::query()->findOrFail($payment->booking_id);
    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();
    $payout->forceFill([
        'status' => MentorPayout::STATUS_TRANSFERRED,
        'stripe_transfer_id' => 'tr_refund_transfer_123',
        'transferred_at' => now(),
    ])->save();

    app(BookingService::class)->cancelBooking($booking, $context['student'], 'Changed plans');

    $refund = BookingRefund::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($refund->status)->toBe(BookingRefund::STATUS_SUCCEEDED)
        ->and($refund->stripe_transfer_reversal_id)->toBe('trr_refund_transfer_123')
        ->and($refund->stripe_refund_id)->toBe('re_after_transfer_123')
        ->and($payout->fresh()->status)->toBe(MentorPayout::STATUS_REVERSED);

    Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/transfers/tr_refund_transfer_123/reversals'
        && str_contains($request->body(), 'amount=8400'));
    Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/refunds');
});

it('marks paid booking cancellation for admin review when stripe refund fails', function () {
    Http::fake([
        'https://api.stripe.com/v1/refunds' => Http::response([
            'error' => [
                'message' => 'Refund failed.',
            ],
        ], 400),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createRefundBookingContext();
    $payment = createRefundPaidPayment($context, 'pi_refund_fail_123');
    $booking = Booking::query()->findOrFail($payment->booking_id);

    app(BookingService::class)->cancelBooking($booking, $context['student'], 'Changed plans');

    $refund = BookingRefund::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($booking->fresh()->status)->toBe('cancelled_pending_refund')
        ->and($refund->status)->toBe(BookingRefund::STATUS_REQUIRES_ADMIN_REVIEW)
        ->and($refund->failure_reason)->toContain('Refund failed');
});

it('does not refund student when transferred payout reversal fails', function () {
    Http::fake([
        'https://api.stripe.com/v1/transfers/tr_reversal_fail_123/reversals' => Http::response([
            'error' => [
                'message' => 'Transfer reversal failed.',
            ],
        ], 400),
        'https://api.stripe.com/v1/refunds' => Http::response([
            'id' => 're_should_not_happen',
        ], 200),
    ]);
    $this->app->instance(HttpFactory::class, Http::getFacadeRoot());

    $context = createRefundBookingContext();
    $payment = createRefundPaidPayment($context, 'pi_reversal_fail_123');
    $booking = Booking::query()->findOrFail($payment->booking_id);
    $payout = MentorPayout::query()->where('booking_id', $booking->id)->firstOrFail();
    $payout->forceFill([
        'status' => MentorPayout::STATUS_TRANSFERRED,
        'stripe_transfer_id' => 'tr_reversal_fail_123',
        'transferred_at' => now(),
    ])->save();

    app(BookingService::class)->cancelBooking($booking, $context['student'], 'Changed plans');

    $refund = BookingRefund::query()->where('booking_id', $booking->id)->firstOrFail();

    expect($booking->fresh()->status)->toBe('cancelled_pending_refund')
        ->and($refund->status)->toBe(BookingRefund::STATUS_REQUIRES_ADMIN_REVIEW)
        ->and($refund->stripe_refund_id)->toBeNull()
        ->and($refund->failure_reason)->toContain('Transfer reversal failed');

    Http::assertNotSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/refunds');
});

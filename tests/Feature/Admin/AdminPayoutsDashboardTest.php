<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Bookings\app\Models\Booking;
use Modules\Institutions\app\Models\University;
use Modules\Payments\app\Models\BookingPayment;
use Modules\Payments\app\Models\MentorPayout;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('mentor', 'web');
    Role::findOrCreate('student', 'web');
});

function createPayoutDashboardUser(string $role, string $prefix): User
{
    $user = User::factory()->create([
        'name' => str($prefix)->replace('-', ' ')->title()->toString(),
        'email' => $prefix.'-'.Str::uuid().'@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

function createPayoutDashboardRecord(array $overrides = []): MentorPayout
{
    $student = $overrides['student'] ?? createPayoutDashboardUser('student', 'payout-student');
    $mentorUser = $overrides['mentor_user'] ?? createPayoutDashboardUser('mentor', 'payout-mentor');
    $university = University::query()->create([
        'name' => 'Payout University '.Str::lower(Str::random(6)),
        'display_name' => 'Payout University '.Str::lower(Str::random(6)),
        'country' => 'US',
        'alpha_two_code' => 'US',
        'city' => 'Boston',
        'state_province' => 'Massachusetts',
        'is_active' => true,
    ]);

    $mentor = $overrides['mentor'] ?? Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => $university->id,
        'grad_school_display' => $university->display_name,
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
        'stripe_account_id' => $overrides['stripe_account_id'] ?? 'acct_payout_123',
        'payouts_enabled' => $overrides['payouts_enabled'] ?? true,
        'stripe_onboarding_complete' => $overrides['stripe_onboarding_complete'] ?? true,
    ]);

    $service = ServiceConfig::query()->create([
        'service_name' => $overrides['service_name'] ?? 'Payout Interview Prep',
        'service_slug' => 'payout_interview_prep_'.Str::lower(Str::random(8)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 120,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 0,
    ]);

    $booking = Booking::query()->create([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => $overrides['session_type'] ?? '1on1',
        'meeting_type' => 'zoom',
        'session_at' => now()->subDays(7),
        'duration_minutes' => 60,
        'credits_charged' => 1,
        'amount_charged' => $overrides['gross_amount'] ?? 120,
        'currency' => 'USD',
        'status' => $overrides['booking_status'] ?? 'completed',
        'approval_status' => 'approved',
        'completed_at' => now()->subDays(6),
    ]);

    $payment = BookingPayment::query()->create([
        'user_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'booking_id' => $booking->id,
        'session_type' => $booking->session_type,
        'meeting_type' => 'zoom',
        'amount' => $overrides['gross_amount'] ?? 120,
        'currency' => 'USD',
        'guest_participants' => [],
        'request_payload' => [],
        'stripe_checkout_session_id' => 'cs_'.Str::lower(Str::random(16)),
        'stripe_payment_intent_id' => $overrides['stripe_payment_intent_id'] ?? 'pi_payout_123',
        'status' => 'paid',
        'payment_completed_at' => now()->subDays(7),
        'booking_created_at' => now()->subDays(7),
    ]);

    return MentorPayout::query()->create([
        'mentor_id' => $mentor->id,
        'booking_id' => $booking->id,
        'booking_payment_id' => $payment->id,
        'student_id' => $student->id,
        'stripe_account_id' => $mentor->stripe_account_id,
        'amount' => $overrides['mentor_share_amount'] ?? 84,
        'gross_amount' => $overrides['gross_amount'] ?? 120,
        'mentor_share_amount' => $overrides['mentor_share_amount'] ?? 84,
        'platform_fee_amount' => $overrides['platform_fee_amount'] ?? 36,
        'currency' => 'USD',
        'calculation_rule' => $overrides['calculation_rule'] ?? [
            'type' => 'database_service_split',
            'service_slug' => $service->service_slug,
        ],
        'status' => $overrides['status'] ?? MentorPayout::STATUS_TRANSFERRED,
        'stripe_transfer_id' => $overrides['stripe_transfer_id'] ?? 'tr_'.Str::lower(Str::random(16)),
        'stripe_balance_transaction_id' => $overrides['stripe_balance_transaction_id'] ?? 'txn_'.Str::lower(Str::random(16)),
        'failure_reason' => $overrides['failure_reason'] ?? null,
        'eligible_at' => $overrides['eligible_at'] ?? now()->subDays(6),
        'transferred_at' => $overrides['transferred_at'] ?? now()->subDays(5),
        'paid_out_at' => $overrides['paid_out_at'] ?? null,
        'failed_at' => $overrides['failed_at'] ?? null,
        'payout_date' => $overrides['payout_date'] ?? now()->subDays(5),
        'attempt_count' => $overrides['attempt_count'] ?? 1,
        'last_attempt_at' => $overrides['last_attempt_at'] ?? now()->subDays(5),
    ]);
}

it('renders the dedicated admin payouts page with payout rows and navigation', function () {
    $admin = createPayoutDashboardUser('admin', 'payout-admin');
    $payout = createPayoutDashboardRecord();

    $this->actingAs($admin)
        ->get(route('admin.payouts'))
        ->assertOk()
        ->assertSee('Admin Payouts')
        ->assertSee('Payouts')
        ->assertSee('#'.$payout->id)
        ->assertSee('Payout Mentor')
        ->assertSee('USD 84')
        ->assertSee('Transferred');
});

it('renders payout details in a dialog when a payout is selected', function () {
    $admin = createPayoutDashboardUser('admin', 'payout-admin');
    $payout = createPayoutDashboardRecord([
        'failure_reason' => 'Temporary Stripe issue',
        'status' => MentorPayout::STATUS_FAILED,
        'failed_at' => now()->subDays(2),
        'transferred_at' => null,
        'payout_date' => null,
        'stripe_transfer_id' => null,
        'stripe_balance_transaction_id' => 'txn_payout_123',
        'attempt_count' => 3,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payouts.show', $payout->id))
        ->assertOk()
        ->assertSee('Payout #'.$payout->id)
        ->assertSee('Stripe Account')
        ->assertSee('acct_payout_123')
        ->assertSee('Payouts enabled')
        ->assertSee('Payout Student')
        ->assertSee('Payout Interview Prep')
        ->assertSee('pi_payout_123')
        ->assertSee('txn_payout_123')
        ->assertSee('Temporary Stripe issue')
        ->assertSee('database_service_split')
        ->assertDontSee('Save Reply');
});

it('filters payouts by status, mentor search, and date range', function () {
    $admin = createPayoutDashboardUser('admin', 'payout-admin');
    createPayoutDashboardRecord([
        'mentor_user' => createPayoutDashboardUser('mentor', 'target-mentor'),
        'status' => MentorPayout::STATUS_FAILED,
        'failure_reason' => 'Needs retry',
        'failed_at' => now()->subDays(3),
        'transferred_at' => null,
        'payout_date' => null,
        'stripe_transfer_id' => null,
    ]);
    createPayoutDashboardRecord([
        'mentor_user' => createPayoutDashboardUser('mentor', 'other-mentor'),
        'status' => MentorPayout::STATUS_TRANSFERRED,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payouts', [
            'status' => MentorPayout::STATUS_FAILED,
            'q' => 'target-mentor',
            'range' => '30d',
        ]))
        ->assertOk()
        ->assertSee('Target Mentor')
        ->assertSee('Failed')
        ->assertSee('Needs retry')
        ->assertDontSee('Other Mentor');
});

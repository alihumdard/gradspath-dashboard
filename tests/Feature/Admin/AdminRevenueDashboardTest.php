<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Bookings\app\Models\Booking;
use Modules\Institutions\app\Models\University;
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

function createRevenueDashboardUser(string $role): User
{
    $user = User::factory()->create([
        'email' => Str::uuid() . '@example.com',
        'is_active' => true,
    ]);

    $user->assignRole($role);

    return $user;
}

function createRevenueBooking(array $overrides = []): Booking
{
    $student = $overrides['student'] ?? createRevenueDashboardUser('student');
    $mentorUser = $overrides['mentor_user'] ?? createRevenueDashboardUser('mentor');
    $university = $overrides['university'] ?? University::query()->create([
        'name' => 'Revenue University ' . Str::lower(Str::random(6)),
        'display_name' => 'Revenue University ' . Str::lower(Str::random(6)),
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
    ]);

    $service = $overrides['service'] ?? ServiceConfig::query()->create([
        'service_name' => 'Interview Prep ' . Str::lower(Str::random(6)),
        'service_slug' => 'interview_prep_' . Str::lower(Str::random(8)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 100,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 0,
        'credit_cost_1on5' => 0,
        'sort_order' => 0,
    ]);

    $attributes = array_merge([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->subDay(),
        'duration_minutes' => 60,
        'credits_charged' => 1,
        'amount_charged' => 100,
        'status' => 'completed',
    ], collect($overrides)->except([
        'student',
        'mentor_user',
        'university',
        'mentor',
        'service',
        'created_at',
        'updated_at',
        'cancelled_at',
    ])->all());

    $booking = Booking::query()->create($attributes);

    DB::table('bookings')
        ->where('id', $booking->id)
        ->update([
            'created_at' => $overrides['created_at'] ?? now(),
            'updated_at' => $overrides['updated_at'] ?? now(),
            'cancelled_at' => $overrides['cancelled_at'] ?? null,
        ]);

    return $booking->fresh();
}

it('builds dynamic revenue data for the default 30 day range', function () {
    $mentorUser = createRevenueDashboardUser('mentor');
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => null,
        'grad_school_display' => 'Revenue Mentor',
        'mentor_type' => 'graduate',
        'program_type' => 'mba',
        'status' => 'active',
    ]);

    createRevenueBooking([
        'mentor' => $mentor,
        'amount_charged' => 150,
        'created_at' => now()->subDays(10),
    ]);

    createRevenueBooking([
        'mentor' => $mentor,
        'amount_charged' => 75,
        'status' => 'cancelled_pending_refund',
        'created_at' => now()->subDays(15),
        'cancelled_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    createRevenueBooking([
        'mentor' => $mentor,
        'amount_charged' => 300,
        'status' => 'cancelled',
        'created_at' => now()->subDays(3),
    ]);

    createRevenueBooking([
        'mentor' => $mentor,
        'amount_charged' => 500,
        'created_at' => now()->subDays(45),
    ]);

    DB::table('mentor_payouts')->insert([
        'mentor_id' => $mentor->id,
        'gross_amount' => 50,
        'mentor_share_amount' => 40,
        'platform_fee_amount' => 10,
        'currency' => 'USD',
        'amount' => 40,
        'status' => 'transferred',
        'payout_date' => now()->subDays(7),
        'transferred_at' => now()->subDays(7),
        'paid_out_at' => null,
        'eligible_at' => null,
        'created_at' => now()->subDays(8),
        'updated_at' => now()->subDays(7),
    ]);

    DB::table('mentor_payouts')->insert([
        'mentor_id' => $mentor->id,
        'gross_amount' => 20,
        'mentor_share_amount' => 10,
        'platform_fee_amount' => 10,
        'currency' => 'USD',
        'amount' => 10,
        'status' => 'ready',
        'payout_date' => null,
        'transferred_at' => null,
        'paid_out_at' => null,
        'eligible_at' => now()->subDays(4),
        'created_at' => now()->subDays(4),
        'updated_at' => now()->subDays(4),
    ]);

    DB::table('mentor_payouts')->insert([
        'mentor_id' => $mentor->id,
        'gross_amount' => 1200,
        'mentor_share_amount' => 999,
        'platform_fee_amount' => 201,
        'currency' => 'USD',
        'amount' => 999,
        'status' => 'paid_out',
        'payout_date' => now()->subDays(90),
        'transferred_at' => null,
        'paid_out_at' => now()->subDays(90),
        'eligible_at' => null,
        'created_at' => now()->subDays(90),
        'updated_at' => now()->subDays(90),
    ]);

    $revenue = app(\Modules\Discovery\app\Services\AdminRevenueService::class)->build();

    expect($revenue['selected_range'])->toBe('30d');
    expect($revenue['summary']['gross_revenue'])->toBe(150.0);
    expect($revenue['summary']['mentor_payouts_paid'])->toBe(40.0);
    expect($revenue['summary']['mentor_payouts_pending'])->toBe(10.0);
    expect($revenue['summary']['mentor_payouts_failed'])->toBe(0.0);
    expect($revenue['summary']['mentor_payouts_total'])->toBe(50.0);
    expect($revenue['summary']['platform_revenue'])->toBe(100.0);
    expect($revenue['summary']['refund_amount'])->toBe(75.0);
    expect($revenue['charts']['program_revenue'])->toMatchArray([
        ['label' => 'MBA', 'value' => 150.0],
    ]);
    expect($revenue['charts']['top_mentors'])->toMatchArray([
        ['label' => $mentorUser->name, 'value' => 150.0],
    ]);
});

it('applies alternate revenue ranges and renders the selector on the dashboard', function () {
    $admin = createRevenueDashboardUser('admin');
    $mentorUser = createRevenueDashboardUser('mentor');
    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'university_id' => null,
        'grad_school_display' => 'Revenue Mentor',
        'mentor_type' => 'graduate',
        'program_type' => null,
        'status' => 'active',
    ]);

    createRevenueBooking([
        'mentor' => $mentor,
        'amount_charged' => 80,
        'created_at' => now()->subDays(45),
    ]);

    createRevenueBooking([
        'mentor' => $mentor,
        'amount_charged' => 30,
        'status' => 'cancelled_pending_refund',
        'created_at' => now()->subDays(50),
        'cancelled_at' => now()->subDays(40),
        'updated_at' => now()->subDays(40),
    ]);

    DB::table('mentor_payouts')->insert([
        'mentor_id' => $mentor->id,
        'gross_amount' => 25,
        'mentor_share_amount' => 15,
        'platform_fee_amount' => 10,
        'currency' => 'USD',
        'amount' => 15,
        'status' => 'ready',
        'payout_date' => null,
        'eligible_at' => now()->subDays(35),
        'created_at' => now()->subDays(35),
        'updated_at' => now()->subDays(35),
    ]);

    $revenue = app(\Modules\Discovery\app\Services\AdminRevenueService::class)->build('60d');

    expect($revenue['selected_range'])->toBe('60d');
    expect($revenue['summary']['gross_revenue'])->toBe(80.0);
    expect($revenue['summary']['refund_amount'])->toBe(30.0);
    expect($revenue['summary']['mentor_payouts_total'])->toBe(15.0);
    expect($revenue['charts']['program_revenue'])->toMatchArray([
        ['label' => 'Unknown', 'value' => 80.0],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.revenue', ['revenue_range' => '60d']))
        ->assertOk()
        ->assertSee('Revenue')
        ->assertSee('Last 60 Days')
        ->assertSee('Top Mentors by Revenue')
        ->assertSee('View payout ledger')
        ->assertDontSee('Recent Mentor Payouts')
        ->assertSee('value="60d" selected', false);
});

it('returns zeroed revenue summaries and empty chart arrays when no data matches', function () {
    $revenue = app(\Modules\Discovery\app\Services\AdminRevenueService::class)->build();

    expect($revenue['summary']['gross_revenue'])->toBe(0.0);
    expect($revenue['summary']['mentor_payouts_total'])->toBe(0.0);
    expect($revenue['summary']['mentor_payouts_paid'])->toBe(0.0);
    expect($revenue['summary']['mentor_payouts_pending'])->toBe(0.0);
    expect($revenue['summary']['mentor_payouts_failed'])->toBe(0.0);
    expect($revenue['summary']['platform_revenue'])->toBe(0.0);
    expect($revenue['summary']['refund_amount'])->toBe(0.0);
    expect($revenue['charts']['program_revenue'])->toBe([]);
    expect($revenue['charts']['top_mentors'])->toBe([]);
});

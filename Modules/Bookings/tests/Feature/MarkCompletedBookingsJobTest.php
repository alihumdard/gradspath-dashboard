<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Jobs\MarkCompletedBookingsJob;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Services\MarkCompletedBookingsService;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('marks past confirmed bookings completed from the queued job', function () {
    $mentorUser = User::factory()->create([
        'is_active' => true,
    ]);

    $student = User::factory()->create([
        'is_active' => true,
    ]);

    $mentor = Mentor::query()->create([
        'user_id' => $mentorUser->id,
        'mentor_type' => 'graduate',
        'status' => 'active',
    ]);

    $service = ServiceConfig::query()->create([
        'service_name' => 'Completion Test',
        'service_slug' => 'completion-test-'.Str::uuid(),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 65,
    ]);

    $past = Booking::query()->create([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->subHours(2),
        'session_timezone' => 'UTC',
        'duration_minutes' => 60,
        'meeting_type' => 'zoom',
        'credits_charged' => 0,
        'amount_charged' => 65,
        'currency' => 'USD',
        'status' => 'confirmed',
        'approval_status' => 'not_required',
    ]);

    $future = Booking::query()->create([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->addHours(2),
        'session_timezone' => 'UTC',
        'duration_minutes' => 60,
        'meeting_type' => 'zoom',
        'credits_charged' => 0,
        'amount_charged' => 65,
        'currency' => 'USD',
        'status' => 'confirmed',
        'approval_status' => 'not_required',
    ]);

    (new MarkCompletedBookingsJob)->handle(app(MarkCompletedBookingsService::class));

    expect($past->fresh()->status)->toBe('completed')
        ->and($past->fresh()->completion_source)->toBe('schedule')
        ->and($future->fresh()->status)->toBe('confirmed');
});

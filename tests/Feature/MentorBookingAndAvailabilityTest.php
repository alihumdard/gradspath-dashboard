<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Events\ChatMessageSent;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\Chat;
use Modules\Bookings\app\Models\MentorAvailabilityRule;
use Modules\Payments\app\Models\ServiceConfig;
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

function makePortalUser(string $prefix, string $role): User
{
    $user = User::factory()->create([
        'email' => $prefix.'-'.Str::uuid().'@example.edu',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $user->assignRole($role);

    return $user;
}

function makePortalMentor(string $prefix, string $mentorType = 'graduate'): array
{
    $user = makePortalUser($prefix, 'mentor');

    $mentor = Mentor::query()->create([
        'user_id' => $user->id,
        'mentor_type' => $mentorType,
        'program_type' => $mentorType === 'professional' ? 'other' : 'mba',
        'grad_school_display' => $mentorType === 'professional' ? 'Industry' : 'Harvard',
        'status' => 'active',
    ]);

    return [$user, $mentor];
}

function makePortalService(array $overrides = []): ServiceConfig
{
    return ServiceConfig::query()->create(array_merge([
        'service_name' => 'Program Insights',
        'service_slug' => 'program-insights-'.Str::lower(Str::random(8)),
        'duration_minutes' => 60,
        'is_active' => true,
        'price_1on1' => 95,
        'platform_fee_1on1' => 35,
        'mentor_payout_1on1' => 60,
        'price_1on3_per_person' => 75,
        'price_1on3_total' => 225,
        'platform_fee_1on3' => 100,
        'mentor_payout_1on3' => 125,
        'price_1on5_per_person' => 60,
        'price_1on5_total' => 300,
        'platform_fee_1on5' => 135,
        'mentor_payout_1on5' => 165,
        'is_office_hours' => false,
        'office_hours_mentor_payout_per_attendee' => null,
        'credit_cost_1on1' => 1,
        'credit_cost_1on3' => 1,
        'credit_cost_1on5' => 1,
        'sort_order' => 1,
    ], $overrides));
}

function attachServiceToMentor(Mentor $mentor, ServiceConfig $service): void
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

function nextWeekdayDate(string $dayOfWeek): string
{
    return now()->next(match ($dayOfWeek) {
        'mon' => Carbon\Carbon::MONDAY,
        'tue' => Carbon\Carbon::TUESDAY,
        'wed' => Carbon\Carbon::WEDNESDAY,
        'thu' => Carbon\Carbon::THURSDAY,
        'fri' => Carbon\Carbon::FRIDAY,
        'sat' => Carbon\Carbon::SATURDAY,
        default => Carbon\Carbon::SUNDAY,
    })->toDateString();
}

function dateSlotsPayload(array $entries): string
{
    return json_encode(array_values($entries), JSON_THROW_ON_ERROR);
}

function createGenericRule(Mentor $mentor, string $dayOfWeek, string $start = '08:00:00', string $end = '10:00:00', ?int $serviceConfigId = null): MentorAvailabilityRule
{
    return MentorAvailabilityRule::query()->create([
        'mentor_id' => $mentor->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => $start,
        'end_time' => $end,
        'timezone' => 'America/New_York',
        'slot_duration_minutes' => 60,
        'session_type' => '1on1',
        'service_config_id' => $serviceConfigId,
        'max_participants' => 1,
        'frequency' => 'weekly',
        'is_active' => true,
    ]);
}

function createGenericSlot(Mentor $mentor, string $date, string $start = '08:00:00', string $end = '09:00:00', ?int $ruleId = null, ?int $serviceConfigId = null): int
{
    return DB::table('mentor_availability_slots')->insertGetId([
        'mentor_id' => $mentor->id,
        'availability_rule_id' => $ruleId,
        'service_config_id' => $serviceConfigId,
        'slot_date' => $date,
        'start_time' => $start,
        'end_time' => $end,
        'timezone' => 'America/New_York',
        'session_type' => '1on1',
        'max_participants' => 1,
        'booked_participants_count' => 0,
        'is_booked' => false,
        'is_blocked' => false,
        'is_active' => true,
        'notes' => 'Generic mentor availability slot',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function fakeStripeCheckoutSession(string $sessionId = 'cs_test_mentor_booking', string $paymentIntent = 'pi_test_mentor_booking'): void
{
    config([
        'services.stripe.secret_key' => 'sk_test_123',
        'services.stripe.api_base' => 'https://stripe.test',
    ]);

    Http::fake([
        'https://stripe.test/checkout/sessions' => Http::response([
            'id' => $sessionId,
            'url' => 'https://stripe.test/checkout/'.$sessionId,
        ], 200),
        'https://stripe.test/checkout/sessions/*' => Http::response([
            'id' => $sessionId,
            'payment_status' => 'paid',
            'payment_intent' => $paymentIntent,
        ], 200),
    ]);
}

function fakeZoomMeetingStartApi(string $meetingId = 'zoom-start-123', string $startUrl = 'https://zoom.us/s/start-token', string $joinUrl = 'https://zoom.us/j/zoom-start-123'): void
{
    config([
        'services.zoom.enabled' => true,
        'services.zoom.account_id' => 'zoom-account-123',
        'services.zoom.client_id' => 'zoom-client-id',
        'services.zoom.client_secret' => 'zoom-client-secret',
        'services.zoom.api_base' => 'https://api.zoom.us/v2',
    ]);

    Http::fake([
        'https://zoom.us/oauth/token' => Http::response([
            'access_token' => 'zoom-access-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'https://api.zoom.us/v2/meetings/'.$meetingId => Http::response([
            'id' => $meetingId,
            'start_url' => $startUrl,
            'join_url' => $joinUrl,
        ], 200),
    ]);
}

function makeSyncedZoomBooking(Mentor $hostMentor, User $bookerUser, ServiceConfig $service, array $overrides = []): Booking
{
    return Booking::query()->create(array_merge([
        'student_id' => $bookerUser->id,
        'mentor_id' => $hostMentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'requested_group_size' => 1,
        'session_at' => now()->addHour(),
        'session_timezone' => 'UTC',
        'duration_minutes' => 60,
        'meeting_link' => 'https://zoom.us/j/zoom-start-123',
        'meeting_type' => 'zoom',
        'external_calendar_event_id' => 'zoom-start-123',
        'calendar_provider' => 'zoom',
        'calendar_sync_status' => 'synced',
        'status' => 'confirmed',
        'approval_status' => 'approved',
    ], $overrides));
}

it('lets a mentor save date-specific availability and generates service-specific 1on1 slots', function () {
    [$mentorUser, $mentor] = makePortalMentor('availability-host');
    $firstService = makePortalService(['service_name' => 'Program Insights']);
    $secondService = makePortalService(['service_name' => 'Interview Prep']);
    attachServiceToMentor($mentor, $firstService);
    attachServiceToMentor($mentor, $secondService);
    $targetDate = now()->addDays(3)->toDateString();

    $this->actingAs($mentorUser)
        ->patch(route('mentor.availability.update'), [
            'timezone' => 'America/New_York',
            'date_slots_payload' => dateSlotsPayload([
                [
                    'date' => $targetDate,
                    'enabled' => true,
                    'slots' => [
                        ['start_time' => '08:00', 'end_time' => '11:00', 'service_config_id' => $firstService->id],
                        ['start_time' => '13:00', 'end_time' => '15:00', 'service_config_id' => $secondService->id],
                    ],
                ],
            ]),
        ])
        ->assertRedirect(route('mentor.availability.index'));

    $this->assertDatabaseHas('mentor_availability_slots', [
        'mentor_id' => $mentor->id,
        'slot_date' => $targetDate,
        'service_config_id' => $firstService->id,
        'session_type' => '1on1',
        'start_time' => '08:00:00',
        'end_time' => '11:00:00',
    ]);

    $this->assertDatabaseHas('mentor_availability_slots', [
        'mentor_id' => $mentor->id,
        'slot_date' => $targetDate,
        'service_config_id' => $secondService->id,
        'session_type' => '1on1',
        'start_time' => '13:00:00',
        'end_time' => '15:00:00',
    ]);

    $this->assertDatabaseMissing('mentor_availability_slots', [
        'mentor_id' => $mentor->id,
        'slot_date' => now()->addDays(10)->toDateString(),
        'service_config_id' => $firstService->id,
        'session_type' => '1on1',
    ]);
});

it('renders the mentor availability editor page', function () {
    [$mentorUser] = makePortalMentor('availability-page');

    $this->actingAs($mentorUser)
        ->get(route('mentor.availability.index'))
        ->assertOk()
        ->assertSee('Set Your Date-Specific Availability')
        ->assertSee('Office Hours for This Mentor')
        ->assertSee('Enable recurring office hours')
        ->assertSee('Availability Scheduler')
        ->assertSee('Saved Dates')
        ->assertSee('Save Availability')
        ->assertSee('mentorAvailabilityPayload', false)
        ->assertSee('availabilityMonthGrid', false)
        ->assertSee('availabilityDayPanel', false);
});

it('defaults mentor availability and office-hours timezones to utc on the availability page', function () {
    [$mentorUser] = makePortalMentor('availability-default-utc');

    $this->actingAs($mentorUser)
        ->get(route('mentor.availability.index'))
        ->assertOk()
        ->assertViewHas('availabilityData', fn (array $data) => ($data['timezone'] ?? null) === 'UTC')
        ->assertViewHas('officeHoursConfig', fn (array $data) => ($data['timezone'] ?? null) === 'UTC')
        ->assertViewHas('schedulerPayload', fn (array $data) => ($data['timezone'] ?? null) === 'UTC');
});

it('defaults mentor availability and office-hours timezones to the saved user timezone when present', function () {
    [$mentorUser] = makePortalMentor('availability-default-user-timezone');

    DB::table('user_settings')->insert([
        'user_id' => $mentorUser->id,
        'theme' => 'light',
        'email_notifications' => true,
        'sms_notifications' => false,
        'timezone' => 'Asia/Karachi',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($mentorUser)
        ->get(route('mentor.availability.index'))
        ->assertOk()
        ->assertViewHas('availabilityData', fn (array $data) => ($data['timezone'] ?? null) === 'Asia/Karachi')
        ->assertViewHas('officeHoursConfig', fn (array $data) => ($data['timezone'] ?? null) === 'Asia/Karachi')
        ->assertViewHas('schedulerPayload', fn (array $data) => ($data['timezone'] ?? null) === 'Asia/Karachi');
});

it('lets a mentor save recurring office hours on the availability page', function () {
    [$mentorUser, $mentor] = makePortalMentor('office-hours-save');
    $service = makePortalService(['service_name' => 'Interview Prep']);
    attachServiceToMentor($mentor, $service);

    $this->actingAs($mentorUser)
        ->patch(route('mentor.availability.update'), [
            'timezone' => 'America/New_York',
            'date_slots_payload' => dateSlotsPayload([]),
            'office_hours' => [
                'enabled' => '1',
                'service_config_id' => $service->id,
                'day_of_week' => 'sun',
                'start_time' => '20:00',
                'timezone' => 'America/New_York',
                'frequency' => 'weekly',
            ],
        ])
        ->assertRedirect(route('mentor.availability.index'));

    $this->assertDatabaseHas('office_hour_schedules', [
        'mentor_id' => $mentor->id,
        'current_service_id' => $service->id,
        'day_of_week' => 'sun',
        'start_time' => '20:00:00',
        'timezone' => 'America/New_York',
        'frequency' => 'weekly',
        'max_spots' => 3,
        'is_active' => true,
    ]);

    expect($mentor->fresh()->office_hours_schedule)->toContain('Sunday')
        ->toContain('8:00 PM');
});

it('updates the default service for editable upcoming office-hour sessions', function () {
    [$mentorUser, $mentor] = makePortalMentor('office-hours-sync');
    $initialService = makePortalService(['service_name' => 'Tutoring']);
    $updatedService = makePortalService(['service_name' => 'Program Insights']);
    attachServiceToMentor($mentor, $initialService);
    attachServiceToMentor($mentor, $updatedService);

    $scheduleId = DB::table('office_hour_schedules')->insertGetId([
        'mentor_id' => $mentor->id,
        'current_service_id' => $initialService->id,
        'day_of_week' => 'sun',
        'start_time' => '20:00:00',
        'timezone' => 'America/New_York',
        'frequency' => 'weekly',
        'max_spots' => 3,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $sessionId = DB::table('office_hour_sessions')->insertGetId([
        'schedule_id' => $scheduleId,
        'current_service_id' => $initialService->id,
        'session_date' => now()->addWeek()->toDateString(),
        'start_time' => '20:00:00',
        'timezone' => 'America/New_York',
        'current_occupancy' => 0,
        'max_spots' => 3,
        'is_full' => false,
        'service_locked' => false,
        'status' => 'upcoming',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($mentorUser)
        ->patch(route('mentor.availability.update'), [
            'timezone' => 'America/New_York',
            'date_slots_payload' => dateSlotsPayload([]),
            'office_hours' => [
                'enabled' => '1',
                'service_config_id' => $updatedService->id,
                'day_of_week' => 'sun',
                'start_time' => '20:00',
                'timezone' => 'America/New_York',
                'frequency' => 'weekly',
            ],
        ])
        ->assertRedirect(route('mentor.availability.index'));

    $this->assertDatabaseHas('office_hour_sessions', [
        'id' => $sessionId,
        'current_service_id' => $updatedService->id,
    ]);
});

it('returns validation errors when office hours are enabled without a valid service', function () {
    [$mentorUser] = makePortalMentor('office-hours-errors');

    $this->actingAs($mentorUser)
        ->patchJson(route('mentor.availability.update'), [
            'timezone' => 'America/New_York',
            'date_slots_payload' => dateSlotsPayload([]),
            'office_hours' => [
                'enabled' => true,
                'day_of_week' => 'sun',
                'start_time' => '20:00',
                'timezone' => 'America/New_York',
                'frequency' => 'weekly',
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['office_hours.service_config_id'])
        ->assertJsonFragment(['Add at least one active mentor service before enabling office hours.']);
});

it('returns scheduler hydration data when saving availability as json', function () {
    [$mentorUser, $mentor] = makePortalMentor('availability-json');
    $service = makePortalService();
    attachServiceToMentor($mentor, $service);
    $targetDate = now()->addDays(4)->toDateString();

    $this->actingAs($mentorUser)
        ->patchJson(route('mentor.availability.update'), [
            'timezone' => 'America/New_York',
            'date_slots_payload' => dateSlotsPayload([
                [
                    'date' => $targetDate,
                    'enabled' => true,
                    'slots' => [
                        ['start_time' => '09:00', 'end_time' => '12:00', 'service_config_id' => $service->id],
                    ],
                ],
            ]),
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Mentor availability updated successfully.')
        ->assertJsonPath('formData.timezone', 'America/New_York')
        ->assertJsonPath('formData.date_slots.0.date', $targetDate)
        ->assertJsonPath('formData.date_slots.0.slots.0.start_time', '09:00')
        ->assertJsonPath('formData.date_slots.0.slots.0.service_config_id', $service->id)
        ->assertJsonPath('scheduler.date_slots.0.key', $targetDate)
        ->assertJsonPath('scheduler.date_slots.0.slots.0.start_index', 18)
        ->assertJsonPath('scheduler.date_slots.0.slots.0.service_config_id', $service->id)
        ->assertJsonPath('scheduler.time_options.0.value', '00:00');

    $this->assertDatabaseHas('mentor_availability_slots', [
        'mentor_id' => $mentor->id,
        'slot_date' => $targetDate,
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
        'service_config_id' => $service->id,
    ]);
});

it('returns json validation errors for invalid scheduler edits', function () {
    [$mentorUser, $mentor] = makePortalMentor('availability-json-errors');
    $service = makePortalService();
    attachServiceToMentor($mentor, $service);
    $targetDate = now()->addDays(5)->toDateString();
    $dateLabel = Carbon\Carbon::parse($targetDate)->format('l, F j, Y');

    $this->actingAs($mentorUser)
        ->patchJson(route('mentor.availability.update'), [
            'timezone' => 'America/New_York',
            'date_slots_payload' => dateSlotsPayload([
                [
                    'date' => $targetDate,
                    'enabled' => true,
                    'slots' => [
                        ['start_time' => '09:00', 'end_time' => '11:00', 'service_config_id' => $service->id],
                        ['start_time' => '10:30', 'end_time' => '12:00', 'service_config_id' => $service->id],
                    ],
                ],
            ]),
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Please fix the highlighted availability settings before saving.')
        ->assertJsonValidationErrors(['date_slots.0.slots.1.start_time'])
        ->assertJsonFragment(["{$dateLabel} time blocks cannot overlap."]);
});

it('rejects same-day availability slots that start in the past', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 4, 20, 15, 0, 0, 'America/New_York'));

    [$mentorUser, $mentor] = makePortalMentor('availability-past-today');
    $service = makePortalService();
    attachServiceToMentor($mentor, $service);
    $today = Carbon\Carbon::now('America/New_York')->toDateString();
    $dateLabel = Carbon\Carbon::parse($today)->format('l, F j, Y');

    $this->actingAs($mentorUser)
        ->patchJson(route('mentor.availability.update'), [
            'timezone' => 'America/New_York',
            'date_slots_payload' => dateSlotsPayload([
                [
                    'date' => $today,
                    'enabled' => true,
                    'slots' => [
                        ['start_time' => '09:00', 'end_time' => '10:00', 'service_config_id' => $service->id],
                    ],
                ],
            ]),
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Please fix the highlighted availability settings before saving.')
        ->assertJsonValidationErrors(['date_slots.0.slots.0.start_time'])
        ->assertJsonFragment(["{$dateLabel} time blocks must start in the future."]);

    $this->assertDatabaseMissing('mentor_availability_slots', [
        'mentor_id' => $mentor->id,
        'slot_date' => $today,
        'start_time' => '09:00:00',
        'service_config_id' => $service->id,
    ]);

    Carbon\Carbon::setTestNow();
});

it('allows same-day availability slots that start later in the current utc day', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 4, 20, 15, 20, 0, 'UTC'));

    [$mentorUser, $mentor] = makePortalMentor('availability-future-today-utc');
    $service = makePortalService();
    attachServiceToMentor($mentor, $service);
    $today = Carbon\Carbon::now('UTC')->toDateString();

    $this->actingAs($mentorUser)
        ->patchJson(route('mentor.availability.update'), [
            'timezone' => 'UTC',
            'date_slots_payload' => dateSlotsPayload([
                [
                    'date' => $today,
                    'enabled' => true,
                    'slots' => [
                        ['start_time' => '15:30', 'end_time' => '16:30', 'service_config_id' => $service->id],
                    ],
                ],
            ]),
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Mentor availability updated successfully.');

    $this->assertDatabaseHas('mentor_availability_slots', [
        'mentor_id' => $mentor->id,
        'slot_date' => $today,
        'start_time' => '15:30:00',
        'end_time' => '16:30:00',
        'service_config_id' => $service->id,
        'timezone' => 'UTC',
    ]);

    Carbon\Carbon::setTestNow();
});

it('uses the utc date boundary for availability payloads around midnight', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 4, 20, 0, 15, 0, 'UTC'));

    [$mentorUser] = makePortalMentor('availability-midnight-utc');

    $this->actingAs($mentorUser)
        ->get(route('mentor.availability.index'))
        ->assertOk()
        ->assertViewHas('schedulerPayload', fn (array $data) => ($data['today'] ?? null) === '2026-04-20');

    Carbon\Carbon::setTestNow();
});

it('preserves booked future slots while replacing unbooked generic slots on availability update', function () {
    [$mentorUser, $mentor] = makePortalMentor('availability-preserve');
    $service = makePortalService();
    attachServiceToMentor($mentor, $service);

    $oldRule = createGenericRule($mentor, 'mon', '08:00:00', '10:00:00', $service->id);
    $nextMonday = nextWeekdayDate('mon');
    $bookedSlotId = createGenericSlot($mentor, $nextMonday, '08:00:00', '09:00:00', $oldRule->id, $service->id);
    $freeSlotId = createGenericSlot($mentor, $nextMonday, '09:00:00', '10:00:00', $oldRule->id, $service->id);

    $student = makePortalUser('availability-student', 'student');

    Booking::query()->create([
        'student_id' => $student->id,
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'mentor_availability_slot_id' => $bookedSlotId,
        'session_type' => '1on1',
        'session_at' => Carbon\Carbon::parse($nextMonday.' 08:00:00'),
        'session_timezone' => 'America/New_York',
        'duration_minutes' => 60,
        'meeting_type' => 'zoom',
        'credits_charged' => 1,
        'amount_charged' => 95,
        'currency' => 'USD',
        'status' => 'confirmed',
        'approval_status' => 'not_required',
    ]);

    $this->actingAs($mentorUser)
        ->patch(route('mentor.availability.update'), [
            'timezone' => 'America/New_York',
            'date_slots_payload' => dateSlotsPayload([
                [
                    'date' => $nextMonday,
                    'enabled' => true,
                    'slots' => [
                        [
                            'slot_id' => $bookedSlotId,
                            'start_time' => '08:00',
                            'end_time' => '09:00',
                            'service_config_id' => $service->id,
                            'is_booked' => true,
                            'booking_count' => 1,
                        ],
                        [
                            'start_time' => '10:00',
                            'end_time' => '12:00',
                            'service_config_id' => $service->id,
                        ],
                    ],
                ],
            ]),
        ])
        ->assertRedirect(route('mentor.availability.index'));

    $this->assertDatabaseHas('mentor_availability_slots', [
        'id' => $bookedSlotId,
        'slot_date' => $nextMonday,
        'start_time' => '08:00:00',
    ]);

    $this->assertDatabaseMissing('mentor_availability_slots', [
        'id' => $freeSlotId,
    ]);

    $this->assertDatabaseHas('mentor_availability_slots', [
        'mentor_id' => $mentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'start_time' => '10:00:00',
    ]);
});

it('surfaces service-specific 1on1 availability to both students and mentors', function () {
    [$hostUser, $hostMentor] = makePortalMentor('availability-shared-host');
    [$bookerUser] = makePortalMentor('availability-shared-booker');
    $student = makePortalUser('availability-shared-student', 'student');
    $service = makePortalService();
    attachServiceToMentor($hostMentor, $service);

    $this->actingAs($hostUser)
        ->patch(route('mentor.availability.update'), [
            'timezone' => 'America/New_York',
            'date_slots_payload' => dateSlotsPayload([
                [
                    'date' => now()->addDays(6)->toDateString(),
                    'enabled' => true,
                    'slots' => [['start_time' => '08:00', 'end_time' => '10:00', 'service_config_id' => $service->id]],
                ],
            ]),
        ]);

    $monthsQuery = [
        'mentor_id' => $hostMentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
    ];

    $studentMonths = $this->actingAs($student)
        ->getJson(route('student.bookings.availability.months', $monthsQuery))
        ->assertOk()
        ->json('months');

    $mentorMonths = $this->actingAs($bookerUser)
        ->getJson(route('mentor.bookings.availability.months', $monthsQuery))
        ->assertOk()
        ->json('months');

    expect($studentMonths)->not->toBeEmpty()
        ->and($mentorMonths)->not->toBeEmpty();
});

it('prevents a mentor from booking their own mentor profile', function () {
    [$mentorUser, $mentor] = makePortalMentor('self-book');

    $this->actingAs($mentorUser)
        ->get(route('mentor.mentor.book', $mentor->id))
        ->assertForbidden();
});

it('lets a mentor complete a paid 1on1 booking and shows it in the correct mentor sections', function () {
    [$hostUser, $hostMentor] = makePortalMentor('paid-host', 'professional');
    [$bookerUser, $bookerMentor] = makePortalMentor('paid-booker');
    $service = makePortalService([
        'service_name' => 'Interview Prep',
        'service_slug' => 'interview-prep-'.Str::lower(Str::random(5)),
        'price_1on1' => 120,
    ]);
    attachServiceToMentor($hostMentor, $service);

    $slotId = createGenericSlot($hostMentor, now()->addDays(6)->toDateString(), '13:00:00', '14:00:00', null, $service->id);

    fakeStripeCheckoutSession();

    $this->actingAs($bookerUser)
        ->postJson(route('mentor.bookings.checkout.store'), [
            'mentor_id' => $hostMentor->id,
            'service_config_id' => $service->id,
            'session_type' => '1on1',
            'mentor_availability_slot_id' => $slotId,
        ])
        ->assertOk()
        ->assertJsonPath('session_id', 'cs_test_mentor_booking');

    $this->actingAs($bookerUser)
        ->get(route('mentor.bookings.checkout.success', ['session_id' => 'cs_test_mentor_booking']))
        ->assertRedirect();

    $booking = Booking::query()->latest('id')->firstOrFail();

    expect((int) $booking->student_id)->toBe((int) $bookerUser->id)
        ->and((int) $booking->mentor_id)->toBe((int) $hostMentor->id);

    $this->assertDatabaseHas('booking_participants', [
        'booking_id' => $booking->id,
        'user_id' => $bookerUser->id,
        'participant_role' => 'booker',
    ]);

    $this->actingAs($hostUser)
        ->get(route('mentor.bookings.index'))
        ->assertOk()
        ->assertViewHas('bookingPageData', function (array $data) use ($booking) {
            return collect($data['bookingGroups'] ?? [])
                ->firstWhere('key', 'hosted') !== null
                && collect(collect($data['bookingGroups'])->firstWhere('key', 'hosted')['items'] ?? [])
                    ->contains(fn (array $item) => (int) $item['id'] === (int) $booking->id);
        });

    $this->actingAs($bookerUser)
        ->get(route('mentor.bookings.index'))
        ->assertOk()
        ->assertViewHas('bookingPageData', function (array $data) use ($booking) {
            return collect($data['bookingGroups'] ?? [])
                ->firstWhere('key', 'booked') !== null
                && collect(collect($data['bookingGroups'])->firstWhere('key', 'booked')['items'] ?? [])
                    ->contains(fn (array $item) => (int) $item['id'] === (int) $booking->id);
        });
});

it('allows either mentor participant to cancel a mentor-booked session before 24 hours', function () {
    [$hostUser, $hostMentor] = makePortalMentor('cancel-host', 'professional');
    [$bookerUser] = makePortalMentor('cancel-booker');
    $service = makePortalService();
    attachServiceToMentor($hostMentor, $service);

    $booking = Booking::query()->create([
        'student_id' => $bookerUser->id,
        'mentor_id' => $hostMentor->id,
        'service_config_id' => $service->id,
        'session_type' => '1on1',
        'session_at' => now()->addDays(5),
        'session_timezone' => 'America/New_York',
        'duration_minutes' => 60,
        'meeting_link' => 'https://meet.gradspath.test/session/mentor-booked',
        'meeting_type' => 'zoom',
        'credits_charged' => 0,
        'amount_charged' => 95,
        'currency' => 'USD',
        'pricing_snapshot' => ['service_name' => $service->service_name],
        'status' => 'confirmed',
        'approval_status' => 'not_required',
    ]);

    $this->actingAs($bookerUser)
        ->postJson(route('mentor.bookings.chat.store', $booking->id), [
            'message' => 'Looking forward to our session.',
        ])
        ->assertCreated()
        ->assertJsonPath('message.receiverId', $hostUser->id);

    expect(Chat::query()->where('booking_id', $booking->id)->count())->toBe(1);

    $this->actingAs($hostUser)
        ->patch(route('mentor.bookings.cancel', $booking->id), [
            'reason' => 'Host mentor cancelled before the 24 hour cutoff.',
        ])
        ->assertRedirect(route('mentor.bookings.index'));

    expect($booking->fresh()->status)->toBe('cancelled');
});

it('shows hosted mentors the backend start zoom meeting route instead of the participant join url', function () {
    [$hostUser, $hostMentor] = makePortalMentor('zoom-host', 'professional');
    $bookerUser = makePortalUser('zoom-student', 'student');
    $service = makePortalService();
    $booking = makeSyncedZoomBooking($hostMentor, $bookerUser, $service);

    $this->actingAs($hostUser)
        ->get(route('mentor.bookings.index'))
        ->assertOk()
        ->assertViewHas('bookingPageData', function (array $data) use ($booking) {
            $hostedGroup = collect($data['bookingGroups'] ?? [])->firstWhere('key', 'hosted');
            $hostedItem = collect($hostedGroup['items'] ?? [])->firstWhere('id', $booking->id);

            return $hostedItem !== null
                && $hostedItem['meetingLink'] === route('mentor.bookings.start-meeting', $booking->id)
                && $hostedItem['meetingLinkLabel'] === 'Start Zoom Meeting';
        });
});

it('redirects the host mentor to a fresh zoom start url', function () {
    [$hostUser, $hostMentor] = makePortalMentor('zoom-start-host', 'professional');
    $bookerUser = makePortalUser('zoom-start-student', 'student');
    $service = makePortalService();
    $booking = makeSyncedZoomBooking($hostMentor, $bookerUser, $service);
    fakeZoomMeetingStartApi('zoom-start-123', 'https://zoom.us/s/fresh-host-start');

    $this->actingAs($hostUser)
        ->get(route('mentor.bookings.start-meeting', $booking->id))
        ->assertRedirect('https://zoom.us/s/fresh-host-start');
});

it('blocks a non-host mentor from starting another mentors zoom meeting', function () {
    [$hostUser, $hostMentor] = makePortalMentor('zoom-owner', 'professional');
    [$bookerUser] = makePortalMentor('zoom-booker', 'graduate');
    $service = makePortalService();
    $booking = makeSyncedZoomBooking($hostMentor, $bookerUser, $service, [
        'student_id' => $bookerUser->id,
    ]);

    expect($hostUser->id)->not->toBe($bookerUser->id);

    $this->actingAs($bookerUser)
        ->get(route('mentor.bookings.start-meeting', $booking->id))
        ->assertForbidden();
});

it('blocks students from the mentor zoom start route', function () {
    [$hostUser, $hostMentor] = makePortalMentor('zoom-student-block-host', 'professional');
    $studentUser = makePortalUser('zoom-student-block', 'student');
    $service = makePortalService();
    $booking = makeSyncedZoomBooking($hostMentor, $studentUser, $service);

    expect($hostUser->id)->not->toBe($studentUser->id);

    $this->actingAs($studentUser)
        ->get(route('mentor.bookings.start-meeting', $booking->id))
        ->assertForbidden();
});

it('returns safely when zoom does not provide a host start url', function () {
    [$hostUser, $hostMentor] = makePortalMentor('zoom-missing-start-host', 'professional');
    $bookerUser = makePortalUser('zoom-missing-start-student', 'student');
    $service = makePortalService();
    $booking = makeSyncedZoomBooking($hostMentor, $bookerUser, $service);
    fakeZoomMeetingStartApi('zoom-start-123', '');

    $this->actingAs($hostUser)
        ->from(route('mentor.bookings.index'))
        ->get(route('mentor.bookings.start-meeting', $booking->id))
        ->assertRedirect(route('mentor.bookings.index'))
        ->assertSessionHas('error', 'Zoom did not return a host start link.');
});

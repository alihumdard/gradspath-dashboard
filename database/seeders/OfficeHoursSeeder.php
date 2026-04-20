<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class OfficeHoursSeeder extends Seeder
{
    public function run(): void
    {
        $officeHoursService = ServiceConfig::query()
            ->where('service_slug', 'office_hours')
            ->orWhere('is_office_hours', true)
            ->first();

        if (!$officeHoursService) {
            return;
        }

        $rotatingServices = ServiceConfig::query()
            ->whereIn('service_slug', ['tutoring', 'program_insights', 'interview_prep', 'application_review'])
            ->get()
            ->keyBy('service_slug');

        $mentors = Mentor::query()->with('user')->where('status', 'active')->get();
        $students = User::query()->whereIn('email', [
            'emma.student@gradspath.edu',
            'alex.student@gradspath.edu',
            'mia.student@gradspath.edu',
        ])->get()->values();

        $sessionBlueprints = [
            [
                'offset_days' => 7,
                'occupancy' => 1,
                'max_spots' => 3,
                'status' => 'upcoming',
                'service_slug' => 'tutoring',
            ],
            [
                'offset_days' => 14,
                'occupancy' => 2,
                'max_spots' => 3,
                'status' => 'upcoming',
                'service_slug' => 'program_insights',
            ],
            [
                'offset_days' => 21,
                'occupancy' => 3,
                'max_spots' => 3,
                'status' => 'upcoming',
                'service_slug' => 'interview_prep',
            ],
            [
                'offset_days' => 28,
                'occupancy' => 0,
                'max_spots' => 3,
                'status' => 'upcoming',
                'service_slug' => 'application_review',
            ],
        ];

        $scheduleMap = [
            'sarah.mentor@gradspath.edu' => ['day' => 'tue', 'start' => '17:00:00', 'frequency' => 'weekly'],
            'daniel.mentor@gradspath.edu' => ['day' => 'thu', 'start' => '19:00:00', 'frequency' => 'biweekly'],
            'leah.mentor@gradspath.edu' => ['day' => 'wed', 'start' => '18:00:00', 'frequency' => 'weekly'],
            'abdul.rauf@uol.edu.pk' => ['day' => 'fri', 'start' => '20:00:00', 'frequency' => 'weekly'],
        ];

        foreach ($mentors as $idx => $mentor) {
            $mentorEmail = $mentor->user?->email;
            $scheduleConfig = $scheduleMap[$mentorEmail] ?? [
                'day' => 'mon',
                'start' => '17:00:00',
                'frequency' => 'weekly',
            ];

            $day = $scheduleConfig['day'];
            $start = $scheduleConfig['start'];

            $schedule = DB::table('office_hour_schedules')
                ->where('mentor_id', $mentor->id)
                ->where('day_of_week', $day)
                ->where('start_time', $start)
                ->first();

            if ($schedule) {
                $scheduleId = $schedule->id;
                DB::table('office_hour_schedules')
                    ->where('id', $scheduleId)
                    ->update([
                        'timezone' => 'America/New_York',
                        'frequency' => $scheduleConfig['frequency'],
                        'max_spots' => 3,
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);
            } else {
                $scheduleId = DB::table('office_hour_schedules')->insertGetId([
                    'mentor_id' => $mentor->id,
                    'day_of_week' => $day,
                    'start_time' => $start,
                    'timezone' => 'America/New_York',
                    'frequency' => $scheduleConfig['frequency'],
                    'max_spots' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($sessionBlueprints as $sessionIndex => $blueprint) {
                $sessionDate = now()->addDays($blueprint['offset_days'] + $idx)->toDateString();
                $currentService = $rotatingServices->get($blueprint['service_slug']) ?? $officeHoursService;
                $occupancy = (int) $blueprint['occupancy'];
                $maxSpots = (int) $blueprint['max_spots'];
                $firstBooker = $occupancy > 0 ? $students->get(0) : null;
                $cutoffAt = Carbon::parse($sessionDate.' '.$start, 'America/New_York')->subDay();

                DB::table('office_hour_sessions')->updateOrInsert(
                    [
                        'schedule_id' => $scheduleId,
                        'session_date' => $sessionDate,
                        'start_time' => $start,
                    ],
                    [
                        'current_service_id' => $currentService->id,
                        'timezone' => 'America/New_York',
                        'current_occupancy' => $occupancy,
                        'max_spots' => $maxSpots,
                        'is_full' => $occupancy >= $maxSpots,
                        'service_locked' => $occupancy >= 2,
                        'first_booker_id' => $firstBooker?->id,
                        'first_booked_at' => $firstBooker ? now()->subDays(2 + $sessionIndex) : null,
                        'service_choice_cutoff_at' => $cutoffAt,
                        'status' => $blueprint['status'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        foreach ($students as $idx => $student) {
            $program = $idx === 0 ? 'mba' : ($idx === 1 ? 'law' : 'therapy');
            $subscriptionId = 'sub_demo_' . $student->id;

            DB::table('office_hours_subscriptions')->updateOrInsert(
                ['stripe_subscription_id' => $subscriptionId],
                [
                    'user_id' => $student->id,
                    'program' => $program,
                    'stripe_customer_id' => 'cus_demo_' . $student->id,
                    'credits_per_cycle' => 5,
                    'current_period_start' => now()->startOfMonth(),
                    'current_period_end' => now()->endOfMonth(),
                    'status' => 'active',
                    'cancelled_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

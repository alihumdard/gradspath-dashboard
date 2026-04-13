<?php

namespace Database\Seeders;

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

        $mentors = Mentor::query()->with('user')->take(2)->get();
        $students = User::query()->whereIn('email', [
            'emma.student@gradspath.edu',
            'alex.student@gradspath.edu',
        ])->get();

        foreach ($mentors as $idx => $mentor) {
            $day = $idx === 0 ? 'tue' : 'thu';
            $start = $idx === 0 ? '17:00:00' : '19:00:00';

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
                        'frequency' => 'weekly',
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
                    'frequency' => 'weekly',
                    'max_spots' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $sessionDate = now()->addDays(7 + $idx)->toDateString();

            $session = DB::table('office_hour_sessions')
                ->where('schedule_id', $scheduleId)
                ->where('session_date', $sessionDate)
                ->where('start_time', $start)
                ->first();

            if ($session) {
                DB::table('office_hour_sessions')
                    ->where('id', $session->id)
                    ->update([
                        'current_service_id' => $officeHoursService->id,
                        'timezone' => 'America/New_York',
                        'current_occupancy' => 1,
                        'max_spots' => 3,
                        'is_full' => false,
                        'service_locked' => false,
                        'status' => 'upcoming',
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('office_hour_sessions')->insert([
                    'schedule_id' => $scheduleId,
                    'current_service_id' => $officeHoursService->id,
                    'session_date' => $sessionDate,
                    'start_time' => $start,
                    'timezone' => 'America/New_York',
                    'current_occupancy' => 1,
                    'max_spots' => 3,
                    'is_full' => false,
                    'service_locked' => false,
                    'first_booker_id' => $students->first()?->id,
                    'first_booked_at' => now()->subDay(),
                    'service_choice_cutoff_at' => now()->addDays(6),
                    'status' => 'upcoming',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach ($students as $idx => $student) {
            $program = $idx === 0 ? 'mba' : 'law';
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

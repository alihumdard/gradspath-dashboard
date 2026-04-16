<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class MentorAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $services = ServiceConfig::query()->get()->keyBy('service_slug');

        $mentors = Mentor::query()
            ->with('user:id,email')
            ->where('status', 'active')
            ->get()
            ->keyBy(fn (Mentor $mentor) => $mentor->user?->email);

        $definitions = [
            'sarah.mentor@gradspath.edu' => [
                [
                    'service_slug' => 'program_insights',
                    'session_type' => '1on1',
                    'weekday' => Carbon::MONDAY,
                    'time' => '10:00:00',
                    'duration' => 60,
                    'max_participants' => 1,
                ],
                [
                    'service_slug' => 'interview_prep',
                    'session_type' => '1on3',
                    'weekday' => Carbon::WEDNESDAY,
                    'time' => '14:00:00',
                    'duration' => 60,
                    'max_participants' => 3,
                ],
            ],
            'daniel.mentor@gradspath.edu' => [
                [
                    'service_slug' => 'application_review',
                    'session_type' => '1on1',
                    'weekday' => Carbon::TUESDAY,
                    'time' => '11:00:00',
                    'duration' => 60,
                    'max_participants' => 1,
                ],
                [
                    'service_slug' => 'interview_prep',
                    'session_type' => '1on5',
                    'weekday' => Carbon::THURSDAY,
                    'time' => '16:00:00',
                    'duration' => 60,
                    'max_participants' => 5,
                ],
            ],
            'leah.mentor@gradspath.edu' => [
                [
                    'service_slug' => 'program_insights',
                    'session_type' => '1on1',
                    'weekday' => Carbon::FRIDAY,
                    'time' => '12:00:00',
                    'duration' => 60,
                    'max_participants' => 1,
                ],
                [
                    'service_slug' => 'application_review',
                    'session_type' => '1on3',
                    'weekday' => Carbon::SATURDAY,
                    'time' => '15:00:00',
                    'duration' => 60,
                    'max_participants' => 3,
                ],
            ],
            'abdul.rauf@uol.edu.pk' => [
                [
                    'service_slug' => 'program_insights',
                    'session_type' => '1on1',
                    'weekday' => Carbon::MONDAY,
                    'time' => '18:00:00',
                    'duration' => 60,
                    'max_participants' => 1,
                ],
                [
                    'service_slug' => 'interview_prep',
                    'session_type' => '1on3',
                    'weekday' => Carbon::FRIDAY,
                    'time' => '20:00:00',
                    'duration' => 60,
                    'max_participants' => 3,
                ],
            ],
        ];

        foreach ($definitions as $mentorEmail => $slots) {
            $mentor = $mentors->get($mentorEmail);

            if (!$mentor) {
                continue;
            }

            foreach ($slots as $slotDefinition) {
                $service = $services->get($slotDefinition['service_slug']);

                if (!$service) {
                    continue;
                }

                for ($monthOffset = 0; $monthOffset < 3; $monthOffset += 1) {
                    $monthBase = now()->copy()->startOfMonth()->addMonths($monthOffset);

                    for ($weekIndex = 0; $weekIndex < 4; $weekIndex += 1) {
                        $slotDate = $monthBase->copy()->next($slotDefinition['weekday'])->addWeeks($weekIndex);

                        if ($slotDate->lt(now()->startOfDay())) {
                            continue;
                        }

                        $start = Carbon::parse($slotDate->toDateString().' '.$slotDefinition['time'], 'America/New_York');
                        $end = $start->copy()->addMinutes($slotDefinition['duration']);

                        DB::table('mentor_availability_slots')->updateOrInsert(
                            [
                                'mentor_id' => $mentor->id,
                                'slot_date' => $slotDate->toDateString(),
                                'start_time' => $start->format('H:i:s'),
                                'session_type' => $slotDefinition['session_type'],
                            ],
                            [
                                'availability_rule_id' => null,
                                'service_config_id' => $service->id,
                                'end_time' => $end->format('H:i:s'),
                                'timezone' => 'America/New_York',
                                'max_participants' => $slotDefinition['max_participants'],
                                'booked_participants_count' => 0,
                                'is_booked' => false,
                                'is_blocked' => false,
                                'is_active' => true,
                                'notes' => 'Seeded dynamic booking slot',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }
        }
    }
}

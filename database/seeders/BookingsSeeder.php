<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class BookingsSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::query()->whereIn('email', [
            'emma.student@gradspath.edu',
            'alex.student@gradspath.edu',
            'mia.student@gradspath.edu',
        ])->get()->keyBy('email');

        $mentors = Mentor::query()
            ->with('user:id,email')
            ->get()
            ->keyBy(fn(Mentor $mentor) => $mentor->user?->email);

        $services = ServiceConfig::query()->get()->keyBy('service_slug');

        $officeHourSessionId = DB::table('office_hour_sessions')->value('id');

        $rows = [
            [
                'student_id' => $students['emma.student@gradspath.edu']->id ?? null,
                'mentor_id' => $mentors['sarah.mentor@gradspath.edu']->id ?? null,
                'service_config_id' => $services['program_insights']->id ?? null,
                'office_hour_session_id' => null,
                'session_type' => '1on1',
                'session_at' => now()->subDays(10)->setTime(17, 0),
                'session_timezone' => 'America/New_York',
                'duration_minutes' => 60,
                'meeting_link' => 'https://meet.example.com/booking-1001',
                'meeting_type' => 'zoom',
                'credits_charged' => 1,
                'status' => 'completed',
                'feedback_due_at' => now()->subDays(9)->setTime(17, 0),
                'student_feedback_done' => true,
                'mentor_feedback_done' => true,
                'is_group_payer' => false,
                'group_payer_id' => null,
            ],
            [
                'student_id' => $students['alex.student@gradspath.edu']->id ?? null,
                'mentor_id' => $mentors['daniel.mentor@gradspath.edu']->id ?? null,
                'service_config_id' => $services['interview_prep']->id ?? null,
                'office_hour_session_id' => null,
                'session_type' => '1on1',
                'session_at' => now()->subDays(7)->setTime(19, 0),
                'session_timezone' => 'America/New_York',
                'duration_minutes' => 60,
                'meeting_link' => 'https://meet.example.com/booking-1002',
                'meeting_type' => 'google_meet',
                'credits_charged' => 1,
                'status' => 'completed',
                'feedback_due_at' => now()->subDays(6)->setTime(19, 0),
                'student_feedback_done' => true,
                'mentor_feedback_done' => false,
                'is_group_payer' => false,
                'group_payer_id' => null,
            ],
            [
                'student_id' => $students['emma.student@gradspath.edu']->id ?? null,
                'mentor_id' => $mentors['abdul.rauf@uol.edu.pk']->id ?? null,
                'service_config_id' => $services['program_insights']->id ?? null,
                'office_hour_session_id' => null,
                'session_type' => '1on1',
                'session_at' => now()->subDays(12)->setTime(20, 0),
                'session_timezone' => 'Asia/Karachi',
                'duration_minutes' => 60,
                'meeting_link' => 'https://meet.example.com/booking-2001',
                'meeting_type' => 'zoom',
                'credits_charged' => 1,
                'status' => 'completed',
                'feedback_due_at' => now()->subDays(11)->setTime(20, 0),
                'student_feedback_done' => true,
                'mentor_feedback_done' => true,
                'is_group_payer' => false,
                'group_payer_id' => null,
            ],
            [
                'student_id' => $students['alex.student@gradspath.edu']->id ?? null,
                'mentor_id' => $mentors['abdul.rauf@uol.edu.pk']->id ?? null,
                'service_config_id' => $services['interview_prep']->id ?? null,
                'office_hour_session_id' => null,
                'session_type' => '1on1',
                'session_at' => now()->subDays(9)->setTime(21, 0),
                'session_timezone' => 'Asia/Karachi',
                'duration_minutes' => 60,
                'meeting_link' => 'https://meet.example.com/booking-2002',
                'meeting_type' => 'google_meet',
                'credits_charged' => 1,
                'status' => 'completed',
                'feedback_due_at' => now()->subDays(8)->setTime(21, 0),
                'student_feedback_done' => true,
                'mentor_feedback_done' => true,
                'is_group_payer' => false,
                'group_payer_id' => null,
            ],
            [
                'student_id' => $students['mia.student@gradspath.edu']->id ?? null,
                'mentor_id' => $mentors['abdul.rauf@uol.edu.pk']->id ?? null,
                'service_config_id' => $services['office_hours']->id ?? null,
                'office_hour_session_id' => null,
                'session_type' => '1on1',
                'session_at' => now()->subDays(5)->setTime(19, 30),
                'session_timezone' => 'Asia/Karachi',
                'duration_minutes' => 45,
                'meeting_link' => 'https://meet.example.com/booking-2003',
                'meeting_type' => 'zoom',
                'credits_charged' => 1,
                'status' => 'completed',
                'feedback_due_at' => now()->subDays(4)->setTime(19, 30),
                'student_feedback_done' => true,
                'mentor_feedback_done' => true,
                'is_group_payer' => false,
                'group_payer_id' => null,
            ],
            [
                'student_id' => $students['mia.student@gradspath.edu']->id ?? null,
                'mentor_id' => $mentors['leah.mentor@gradspath.edu']->id ?? null,
                'service_config_id' => $services['application_review']->id ?? null,
                'office_hour_session_id' => null,
                'session_type' => '1on1',
                'session_at' => now()->addDays(3)->setTime(18, 0),
                'session_timezone' => 'America/New_York',
                'duration_minutes' => 60,
                'meeting_link' => 'https://meet.example.com/booking-1003',
                'meeting_type' => 'zoom',
                'credits_charged' => 1,
                'status' => 'confirmed',
                'feedback_due_at' => now()->addDays(4)->setTime(18, 0),
                'student_feedback_done' => false,
                'mentor_feedback_done' => false,
                'is_group_payer' => false,
                'group_payer_id' => null,
            ],
            [
                'student_id' => $students['emma.student@gradspath.edu']->id ?? null,
                'mentor_id' => $mentors['sarah.mentor@gradspath.edu']->id ?? null,
                'service_config_id' => $services['office_hours']->id ?? null,
                'office_hour_session_id' => $officeHourSessionId,
                'session_type' => 'office_hours',
                'session_at' => now()->addDays(7)->setTime(17, 0),
                'session_timezone' => 'America/New_York',
                'duration_minutes' => 45,
                'meeting_link' => 'https://meet.example.com/booking-1004',
                'meeting_type' => 'zoom',
                'credits_charged' => 1,
                'status' => 'confirmed',
                'feedback_due_at' => now()->addDays(8)->setTime(17, 0),
                'student_feedback_done' => false,
                'mentor_feedback_done' => false,
                'is_group_payer' => false,
                'group_payer_id' => null,
            ],
        ];

        foreach ($rows as $row) {
            if (!$row['student_id'] || !$row['mentor_id'] || !$row['service_config_id']) {
                continue;
            }

            $booking = Booking::query()->updateOrCreate(
                [
                    'student_id' => $row['student_id'],
                    'mentor_id' => $row['mentor_id'],
                    'service_config_id' => $row['service_config_id'],
                    'session_at' => $row['session_at'],
                ],
                $row
            );

            DB::table('booking_participants')->updateOrInsert(
                [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->student_id,
                ],
                [
                    'participant_role' => 'student',
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

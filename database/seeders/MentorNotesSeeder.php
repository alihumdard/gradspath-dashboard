<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Bookings\app\Models\Booking;

class MentorNotesSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::query()
            ->with('service:id,service_name')
            ->where('status', 'completed')
            ->orderBy('session_at')
            ->take(3)
            ->get();

        foreach ($bookings as $booking) {
            DB::table('mentor_notes')->updateOrInsert(
                [
                    'mentor_id' => $booking->mentor_id,
                    'student_id' => $booking->student_id,
                    'booking_id' => $booking->id,
                ],
                [
                    'session_date' => $booking->session_at->toDateString(),
                    'service_type' => $booking->service?->service_name,
                    'worked_on' => 'Reviewed goals and refined application strategy for this cycle.',
                    'next_steps' => 'Student should finalize essays and schedule one mock interview next week.',
                    'session_result' => 'Clear application plan with deadlines and priority tasks.',
                    'strengths_challenges' => 'Strength: clear motivation. Challenge: concise storytelling.',
                    'other_notes' => 'Student was engaged and asked thoughtful follow-up questions.',
                    'is_deleted' => false,
                    'deleted_by' => null,
                    'deleted_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

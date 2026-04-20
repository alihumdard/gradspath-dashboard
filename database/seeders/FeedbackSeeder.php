<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Models\Feedback;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::query()
            ->with('service:id,service_name')
            ->where('status', 'completed')
            ->orderBy('session_at')
            ->take(3)
            ->get();

        foreach ($bookings as $index => $booking) {
            $stars = max(4, 5 - $index);

            Feedback::query()->updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'student_id' => $booking->student_id,
                ],
                [
                    'mentor_id' => $booking->mentor_id,
                    'stars' => $stars,
                    'preparedness_rating' => $stars,
                    'comment' => 'Very helpful session with clear guidance and practical advice.',
                    'recommend' => true,
                    'service_type' => $booking->service?->service_name,
                    'is_verified' => true,
                    'is_visible' => true,
                    'admin_note' => null,
                    'amended_by' => null,
                    'amended_at' => null,
                ]
            );

            $booking->update([
                'student_feedback_done' => true,
            ]);
        }
    }
}

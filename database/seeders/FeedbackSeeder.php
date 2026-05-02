<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Models\Feedback;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $seededComments = [
            'https://meet.example.com/booking-1001' => 'Very helpful session with clear guidance and practical advice.',
            'https://meet.example.com/booking-1002' => 'Very helpful session with clear guidance and practical advice.',
            'https://meet.example.com/booking-2001' => 'Abdul gave me a much clearer MBA application strategy and broke down what to prioritize first.',
            'https://meet.example.com/booking-2002' => 'Excellent interview prep with direct feedback, better framing, and practical examples I could use right away.',
            'https://meet.example.com/booking-2003' => 'Supportive office hours session with thoughtful answers and actionable next steps for my profile.',
        ];

        $bookings = Booking::query()
            ->with('service:id,service_name')
            ->where('status', 'completed')
            ->orderBy('session_at')
            ->get();

        foreach ($bookings as $index => $booking) {
            $stars = max(4, 5 - $index);
            $comment = $seededComments[$booking->meeting_link] ?? 'Very helpful session with clear guidance and practical advice.';

            Feedback::query()->updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'student_id' => $booking->student_id,
                ],
                [
                    'mentor_id' => $booking->mentor_id,
                    'stars' => $stars,
                    'preparedness_rating' => $stars,
                    'comment' => $comment,
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

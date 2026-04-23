<?php

namespace Modules\Feedback\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Feedback\app\Events\FeedbackSubmitted;
use Modules\Feedback\app\Models\Feedback;

class FeedbackService
{
    public function __construct(private readonly RatingAggregationService $ratings) {}

    public function storeStudentFeedback(User $student, array $data): Feedback
    {
        return DB::transaction(function () use ($student, $data) {
            $booking = Booking::query()->findOrFail((int) $data['booking_id']);

            if ((int) $booking->student_id !== (int) $student->id) {
                throw new \RuntimeException('You cannot submit feedback for this booking.');
            }

            if (! $booking->feedbackUnlocked()) {
                throw new \RuntimeException('Feedback can only be submitted after attendance is confirmed or the feedback window opens.');
            }

            $feedback = Feedback::query()->updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'student_id' => $student->id,
                ],
                [
                    'mentor_id' => $booking->mentor_id,
                    'stars' => $data['stars'],
                    'preparedness_rating' => $data['preparedness_rating'] ?? null,
                    'comment' => $data['comment'],
                    'recommend' => (bool) ($data['recommend'] ?? true),
                    'service_type' => $data['service_type'] ?? null,
                    'is_verified' => true,
                    'is_visible' => true,
                ]
            );

            $booking->student_feedback_done = true;
            $booking->save();

            event(new FeedbackSubmitted($feedback));

            $this->ratings->recalculate((int) $booking->mentor_id);

            return $feedback;
        });
    }

    public function moderate(int $feedbackId, User $admin, array $data): Feedback
    {
        return DB::transaction(function () use ($feedbackId, $admin, $data) {
            $feedback = Feedback::query()->findOrFail($feedbackId);

            if (array_key_exists('comment', $data) && $feedback->original_comment === null) {
                $feedback->original_comment = $feedback->comment;
            }

            $feedback->fill([
                'comment' => $data['comment'] ?? $feedback->comment,
                'is_visible' => $data['is_visible'] ?? $feedback->is_visible,
                'admin_note' => $data['admin_note'] ?? $feedback->admin_note,
                'amended_by' => $admin->id,
                'amended_at' => now(),
            ]);
            $feedback->save();

            $this->ratings->recalculate((int) $feedback->mentor_id);

            return $feedback;
        });
    }
}

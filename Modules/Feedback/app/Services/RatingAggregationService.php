<?php

namespace Modules\Feedback\app\Services;

use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Models\MentorRating;

class RatingAggregationService
{
    public function recalculate(int $mentorId): MentorRating
    {
        $query = Feedback::query()->where('mentor_id', $mentorId)->where('is_visible', true);

        $totalReviews = (int) $query->count();
        $avgStars = $totalReviews > 0 ? (float) $query->avg('stars') : 0.0;
        $recommendCount = (int) $query->where('recommend', true)->count();
        $recommendRate = $totalReviews > 0 ? round(($recommendCount / $totalReviews) * 100, 2) : 0.0;

        return MentorRating::query()->updateOrCreate(
            ['mentor_id' => $mentorId],
            [
                'avg_stars' => $avgStars,
                'recommend_rate' => $recommendRate,
                'total_reviews' => $totalReviews,
                'recalculated_at' => now(),
            ]
        );
    }
}

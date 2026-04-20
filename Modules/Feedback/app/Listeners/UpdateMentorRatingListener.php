<?php

namespace Modules\Feedback\app\Listeners;

use Modules\Feedback\app\Events\FeedbackSubmitted;
use Modules\Feedback\app\Services\RatingAggregationService;

class UpdateMentorRatingListener
{
    public function __construct(private readonly RatingAggregationService $ratings) {}

    public function handle(FeedbackSubmitted $event): void
    {
        $this->ratings->recalculate((int) $event->feedback->mentor_id);
    }
}

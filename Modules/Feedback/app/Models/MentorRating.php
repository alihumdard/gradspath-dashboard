<?php

namespace Modules\Feedback\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Settings\app\Models\Mentor;

class MentorRating extends Model
{
    protected $table = 'mentor_ratings';

    protected $fillable = [
        'mentor_id',
        'avg_stars',
        'recommend_rate',
        'total_reviews',
        'total_sessions',
        'top_tag',
        'top_tags_json',
        'recalculated_at',
    ];

    protected $casts = [
        'avg_stars' => 'decimal:2',
        'recommend_rate' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_sessions' => 'integer',
        'top_tags_json' => 'array',
        'recalculated_at' => 'datetime',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }
}

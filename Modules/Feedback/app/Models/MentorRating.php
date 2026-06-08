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
        'admin_rating_override',
        'admin_rating_override_note',
        'admin_rating_overridden_by',
        'admin_rating_overridden_at',
        'recommend_rate',
        'total_reviews',
        'total_sessions',
        'top_tag',
        'top_tags_json',
        'recalculated_at',
    ];

    protected $casts = [
        'avg_stars' => 'decimal:2',
        'admin_rating_override' => 'decimal:2',
        'recommend_rate' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_sessions' => 'integer',
        'top_tags_json' => 'array',
        'admin_rating_overridden_at' => 'datetime',
        'recalculated_at' => 'datetime',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function getHasAdminRatingOverrideAttribute(): bool
    {
        return $this->admin_rating_override !== null;
    }

    public function getEffectiveRatingAttribute(): ?float
    {
        $rating = $this->admin_rating_override ?? $this->avg_stars;

        return $rating !== null ? (float) $rating : null;
    }

    public function getHasEffectiveRatingAttribute(): bool
    {
        return $this->admin_rating_override !== null || (float) ($this->avg_stars ?? 0) > 0;
    }
}

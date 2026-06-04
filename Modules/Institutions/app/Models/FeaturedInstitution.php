<?php

namespace Modules\Institutions\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedInstitution extends Model
{
    public const SOURCE_AUTOMATIC = 'automatic';
    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'university_id',
        'sort_order',
        'meetings_count',
        'source',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'meetings_count' => 'integer',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}

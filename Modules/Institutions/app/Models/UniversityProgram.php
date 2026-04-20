<?php

namespace Modules\Institutions\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversityProgram extends Model
{
    protected $table = 'university_programs';

    protected $fillable = [
        'university_id',
        'program_name',
        'program_type',
        'tier',
        'description',
        'duration_months',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_months' => 'integer',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}

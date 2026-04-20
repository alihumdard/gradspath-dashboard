<?php

namespace Modules\Settings\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;
use Modules\Institutions\app\Models\University;

class StudentProfile extends Model
{
    protected $table = 'student_profiles';

    protected $fillable = [
        'user_id',
        'university_id',
        'institution_text',
        'program_level',
        'program_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}

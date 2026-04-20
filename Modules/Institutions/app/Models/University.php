<?php

namespace Modules\Institutions\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Settings\app\Models\Mentor;
use Modules\Settings\app\Models\StudentProfile;

class University extends Model
{
    protected $table = 'universities';

    protected $fillable = [
        'name',
        'display_name',
        'country',
        'alpha_two_code',
        'city',
        'domains',
        'web_pages',
        'state_province',
        'logo_url',
        'is_active',
    ];

    protected $casts = [
        'domains' => 'array',
        'web_pages' => 'array',
        'is_active' => 'boolean',
    ];

    public function programs(): HasMany
    {
        return $this->hasMany(UniversityProgram::class);
    }

    public function mentors(): HasMany
    {
        return $this->hasMany(Mentor::class);
    }

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }
}

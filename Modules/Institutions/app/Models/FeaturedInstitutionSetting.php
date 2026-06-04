<?php

namespace Modules\Institutions\app\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedInstitutionSetting extends Model
{
    public const MODE_AUTOMATIC = 'automatic';
    public const MODE_MANUAL = 'manual';

    protected $fillable = [
        'mode',
        'last_recalculated_at',
    ];

    protected $casts = [
        'last_recalculated_at' => 'datetime',
    ];
}

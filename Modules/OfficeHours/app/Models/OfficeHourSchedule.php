<?php

namespace Modules\OfficeHours\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class OfficeHourSchedule extends Model
{
    protected $table = 'office_hour_schedules';

    protected $fillable = [
        'mentor_id',
        'current_service_id',
        'day_of_week',
        'start_time',
        'timezone',
        'frequency',
        'max_spots',
        'is_active',
    ];

    protected $casts = [
        'max_spots' => 'integer',
        'is_active' => 'boolean',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function currentService(): BelongsTo
    {
        return $this->belongsTo(ServiceConfig::class, 'current_service_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(OfficeHourSession::class, 'schedule_id');
    }
}

<?php

namespace Modules\OfficeHours\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;
use Modules\Payments\app\Models\ServiceConfig;

class OfficeHourSession extends Model
{
    protected $table = 'office_hour_sessions';

    protected $fillable = [
        'schedule_id',
        'current_service_id',
        'session_date',
        'start_time',
        'timezone',
        'current_occupancy',
        'max_spots',
        'is_full',
        'service_locked',
        'first_booker_id',
        'first_booked_at',
        'service_choice_cutoff_at',
        'status',
    ];

    protected $casts = [
        'session_date' => 'date',
        'first_booked_at' => 'datetime',
        'service_choice_cutoff_at' => 'datetime',
        'is_full' => 'boolean',
        'service_locked' => 'boolean',
        'current_occupancy' => 'integer',
        'max_spots' => 'integer',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(OfficeHourSchedule::class, 'schedule_id');
    }

    public function currentService(): BelongsTo
    {
        return $this->belongsTo(ServiceConfig::class, 'current_service_id');
    }

    public function firstBooker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_booker_id');
    }
}

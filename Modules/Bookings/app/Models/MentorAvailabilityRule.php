<?php

namespace Modules\Bookings\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class MentorAvailabilityRule extends Model
{
    protected $table = 'mentor_availability_rules';

    protected $fillable = [
        'mentor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'timezone',
        'slot_duration_minutes',
        'session_type',
        'service_config_id',
        'max_participants',
        'frequency',
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected $casts = [
        'slot_duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceConfig::class, 'service_config_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(MentorAvailabilitySlot::class, 'availability_rule_id');
    }
}

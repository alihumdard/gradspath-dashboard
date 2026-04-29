<?php

namespace Modules\Bookings\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class MentorAvailabilitySlot extends Model
{
    protected $table = 'mentor_availability_slots';

    protected $fillable = [
        'mentor_id',
        'availability_rule_id',
        'service_config_id',
        'slot_date',
        'start_time',
        'end_time',
        'timezone',
        'starts_at_utc',
        'ends_at_utc',
        'session_type',
        'max_participants',
        'booked_participants_count',
        'is_booked',
        'is_blocked',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'slot_date' => 'date',
        'starts_at_utc' => 'datetime',
        'ends_at_utc' => 'datetime',
        'max_participants' => 'integer',
        'booked_participants_count' => 'integer',
        'is_booked' => 'boolean',
        'is_blocked' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(MentorAvailabilityRule::class, 'availability_rule_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceConfig::class, 'service_config_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'mentor_availability_slot_id');
    }
}

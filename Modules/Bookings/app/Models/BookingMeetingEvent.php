<?php

namespace Modules\Bookings\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingMeetingEvent extends Model
{
    protected $table = 'booking_meeting_events';

    protected $fillable = [
        'booking_id',
        'provider',
        'provider_meeting_id',
        'event_id',
        'event_type',
        'occurred_at',
        'received_at',
        'meeting_started_at',
        'meeting_ended_at',
        'host_joined_at',
        'first_participant_joined_at',
        'is_verified',
        'processed',
        'payload_hash',
        'payload',
        'error_message',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'received_at' => 'datetime',
        'meeting_started_at' => 'datetime',
        'meeting_ended_at' => 'datetime',
        'host_joined_at' => 'datetime',
        'first_participant_joined_at' => 'datetime',
        'is_verified' => 'boolean',
        'processed' => 'boolean',
        'payload' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

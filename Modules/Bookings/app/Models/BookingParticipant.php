<?php

namespace Modules\Bookings\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;

class BookingParticipant extends Model
{
    protected $table = 'booking_participants';

    protected $fillable = [
        'booking_id',
        'user_id',
        'full_name',
        'email',
        'participant_role',
        'is_primary',
        'invite_status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

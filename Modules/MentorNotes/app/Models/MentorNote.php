<?php

namespace Modules\MentorNotes\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;

class MentorNote extends Model
{
    protected $table = 'mentor_notes';

    protected $fillable = [
        'mentor_id',
        'student_id',
        'booking_id',
        'session_date',
        'service_type',
        'worked_on',
        'next_steps',
        'session_result',
        'strengths_challenges',
        'other_notes',
        'is_deleted',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}

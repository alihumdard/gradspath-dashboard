<?php

namespace Modules\Feedback\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'booking_id',
        'student_id',
        'mentor_id',
        'stars',
        'preparedness_rating',
        'comment',
        'recommend',
        'service_type',
        'is_verified',
        'original_comment',
        'is_visible',
        'admin_note',
        'amended_by',
        'amended_at',
        'mentor_reply',
        'replied_at',
    ];

    protected $casts = [
        'stars' => 'integer',
        'preparedness_rating' => 'integer',
        'recommend' => 'boolean',
        'is_verified' => 'boolean',
        'is_visible' => 'boolean',
        'amended_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function amendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'amended_by');
    }
}

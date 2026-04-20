<?php

namespace Modules\Bookings\app\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\Bookings\app\Models\BookingParticipant;
use Modules\Bookings\app\Models\Chat;
use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Models\MentorFeedback;
use Modules\OfficeHours\app\Models\OfficeHourSession;
use Modules\Payments\app\Models\CreditTransaction;
use Modules\Payments\app\Models\ServiceConfig;
use Modules\Settings\app\Models\Mentor;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'student_id',
        'mentor_id',
        'service_config_id',
        'mentor_availability_slot_id',
        'office_hour_session_id',
        'session_type',
        'requested_group_size',
        'session_at',
        'session_timezone',
        'duration_minutes',
        'meeting_link',
        'meeting_type',
        'external_calendar_event_id',
        'calendar_provider',
        'calendar_sync_status',
        'calendar_last_error',
        'credits_charged',
        'amount_charged',
        'currency',
        'pricing_snapshot',
        'status',
        'approval_status',
        'cancelled_at',
        'cancel_reason',
        'cancelled_by',
        'feedback_due_at',
        'student_feedback_done',
        'mentor_feedback_done',
        'is_group_payer',
        'group_payer_id',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'feedback_due_at' => 'datetime',
        'student_feedback_done' => 'boolean',
        'mentor_feedback_done' => 'boolean',
        'is_group_payer' => 'boolean',
        'credits_charged' => 'integer',
        'requested_group_size' => 'integer',
        'amount_charged' => 'decimal:2',
        'pricing_snapshot' => 'array',
    ];

    protected function sessionAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value, 'UTC') : null,
            set: fn ($value) => $value ? Carbon::parse($value)->utc()->toDateTimeString() : null,
        );
    }

    public function isSelfCancellationWindowOpen(): bool
    {
        return $this->session_at !== null && $this->session_at->gt(now()->addDay());
    }

    public function sessionAtInTimezone(?string $timezone = null): ?Carbon
    {
        if (!$this->session_at) {
            return null;
        }

        return $this->session_at->copy()->setTimezone($timezone ?: ($this->session_timezone ?: config('app.timezone', 'UTC')));
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function booker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceConfig::class, 'service_config_id');
    }

    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(MentorAvailabilitySlot::class, 'mentor_availability_slot_id');
    }

    public function officeHourSession(): BelongsTo
    {
        return $this->belongsTo(OfficeHourSession::class, 'office_hour_session_id');
    }

    public function participants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'booking_participants')
            ->withPivot(['participant_role', 'is_primary'])
            ->withTimestamps();
    }

    public function participantRecords(): HasMany
    {
        return $this->hasMany(BookingParticipant::class, 'booking_id');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'booking_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function mentorFeedback(): HasMany
    {
        return $this->hasMany(MentorFeedback::class, 'booking_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }
}

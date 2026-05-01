<?php

namespace Modules\Bookings\app\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\app\Models\User;
use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Models\MentorFeedback;
use Modules\MentorNotes\app\Models\MentorNote;
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
        'completed_at',
        'completion_source',
        'session_outcome',
        'session_outcome_note',
        'attendance_status',
        'actual_started_at',
        'actual_ended_at',
        'host_joined_at',
        'first_attendee_joined_at',
        'attendance_overlap_minutes',
        'feedback_due_at',
        'feedback_unlocked_at',
        'student_feedback_done',
        'mentor_feedback_done',
        'is_group_payer',
        'group_payer_id',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'actual_started_at' => 'datetime',
        'actual_ended_at' => 'datetime',
        'host_joined_at' => 'datetime',
        'first_attendee_joined_at' => 'datetime',
        'feedback_due_at' => 'datetime',
        'feedback_unlocked_at' => 'datetime',
        'student_feedback_done' => 'boolean',
        'mentor_feedback_done' => 'boolean',
        'is_group_payer' => 'boolean',
        'credits_charged' => 'integer',
        'requested_group_size' => 'integer',
        'attendance_overlap_minutes' => 'integer',
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
        if (! $this->session_at) {
            return null;
        }

        return $this->session_at->copy()->setTimezone($timezone ?: ($this->session_timezone ?: config('app.timezone', 'UTC')));
    }

    public function scheduledEndAt(): ?Carbon
    {
        return $this->session_at?->copy()->addMinutes(max((int) $this->duration_minutes, 1));
    }

    public function meetingAccessAllowed(): bool
    {
        return true;
    }

    public function meetingAccessOpensAt(?string $timezone = null): ?Carbon
    {
        return $this->sessionAtInTimezone($timezone);
    }

    public function meetingAccessMessage(?string $timezone = null): string
    {
        $opensAt = $this->meetingAccessOpensAt($timezone);

        if (! $opensAt) {
            return 'Meeting access will be enabled once the session start time is available.';
        }

        if ($this->meetingAccessAllowed()) {
            return 'Meeting access is enabled now.';
        }

        return sprintf(
            'Meeting access will be enabled at %s on %s.',
            $opensAt->format('g:i A'),
            $opensAt->format('F j, Y')
        );
    }

    public function mentorNotesAllowed(): bool
    {
        $meetingEndedAt = $this->mentorNotesAvailableAt();

        if (! $meetingEndedAt) {
            return false;
        }

        return now()->utc()->greaterThanOrEqualTo($meetingEndedAt->copy()->utc());
    }

    public function mentorNotesAvailableAt(?string $timezone = null): ?Carbon
    {
        $meetingEndedAt = $this->actualMeetingEndedAt();

        if (! $meetingEndedAt) {
            return null;
        }

        return $meetingEndedAt->copy()->setTimezone($timezone ?: ($this->session_timezone ?: config('app.timezone', 'UTC')));
    }

    public function mentorNotesMessage(?string $timezone = null): string
    {
        $availableAt = $this->mentorNotesAvailableAt($timezone);

        if (! $availableAt) {
            return 'Mentor notes will be available once the meeting end time is available.';
        }

        if ($this->mentorNotesAllowed()) {
            return 'Mentor notes are available because the meeting has ended.';
        }

        return sprintf(
            'Mentor notes will be enabled after the meeting ends at %s on %s.',
            $availableAt->format('g:i A'),
            $availableAt->format('F j, Y')
        );
    }

    public function actualMeetingEndedAt(): ?Carbon
    {
        if ($this->actual_ended_at) {
            return $this->actual_ended_at->copy();
        }

        $latestEndedAt = $this->relationLoaded('meetingEvents')
            ? $this->meetingEvents
                ->pluck('meeting_ended_at')
                ->filter()
                ->sort()
                ->last()
            : $this->meetingEvents()
                ->whereNotNull('meeting_ended_at')
                ->orderByDesc('meeting_ended_at')
                ->value('meeting_ended_at');

        if ($latestEndedAt instanceof Carbon) {
            return $latestEndedAt->copy();
        }

        return $latestEndedAt ? Carbon::parse($latestEndedAt, 'UTC') : null;
    }

    public function feedbackUnlocked(): bool
    {
        if ($this->attendance_status === 'attended' && $this->actual_ended_at !== null) {
            return $this->actual_ended_at->lessThanOrEqualTo(now());
        }

        $scheduledEnd = $this->scheduledEndAt();

        if ($scheduledEnd && $scheduledEnd->greaterThan(now()->utc())) {
            return false;
        }

        return $this->attendance_status === 'attended'
            || ($this->feedback_unlocked_at !== null && $this->feedback_unlocked_at->lessThanOrEqualTo(now()));
    }

    public function feedbackDueExpired(): bool
    {
        return $this->feedback_due_at !== null && $this->feedback_due_at->lessThan(now());
    }

    public function hasParticipantUser(User $user): bool
    {
        if ($this->relationLoaded('participantRecords')) {
            return $this->participantRecords->contains(function (BookingParticipant $participant) use ($user) {
                return (int) ($participant->user_id ?? 0) === (int) $user->id
                    || (
                        filled($participant->email)
                        && strtolower((string) $participant->email) === strtolower((string) $user->email)
                    );
            });
        }

        return $this->participantRecords()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);

                if (filled($user->email)) {
                    $query->orWhereRaw('LOWER(email) = ?', [strtolower((string) $user->email)]);
                }
            })
            ->exists();
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

    public function meetingEvents(): HasMany
    {
        return $this->hasMany(BookingMeetingEvent::class, 'booking_id');
    }

    public function participants(): BelongsToMany
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

    public function mentorNotes(): HasMany
    {
        return $this->hasMany(MentorNote::class, 'booking_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }
}

<?php

namespace Modules\Settings\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Bookings\app\Models\MentorAvailabilityRule;
use Modules\Bookings\app\Models\MentorAvailabilitySlot;
use Modules\Feedback\app\Models\Feedback;
use Modules\Feedback\app\Models\MentorRating;
use Modules\Institutions\app\Models\University;
use Modules\OfficeHours\app\Models\OfficeHourSchedule;
use Modules\Payments\app\Models\ServiceConfig;

class Mentor extends Model
{
    protected $table = 'mentors';

    protected $fillable = [
        'user_id',
        'university_id',
        'title',
        'grad_school_display',
        'mentor_type',
        'program_type',
        'bio',
        'description',
        'office_hours_schedule',
        'avatar_url',
        'avatar_crop_zoom',
        'avatar_crop_x',
        'avatar_crop_y',
        'edu_email',
        'calendly_link',
        'slack_link',
        'is_featured',
        'stripe_account_id',
        'payouts_enabled',
        'stripe_onboarding_complete',
        'status',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'payouts_enabled' => 'boolean',
        'stripe_onboarding_complete' => 'boolean',
        'approved_at' => 'datetime',
        'avatar_crop_zoom' => 'float',
        'avatar_crop_x' => 'float',
        'avatar_crop_y' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(ServiceConfig::class, 'mentor_services')
            ->withPivot(['sort_order'])
            ->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(MentorAvailabilityRule::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(MentorAvailabilitySlot::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(MentorRating::class);
    }

    public function officeHourSchedules(): HasMany
    {
        return $this->hasMany(OfficeHourSchedule::class);
    }
}

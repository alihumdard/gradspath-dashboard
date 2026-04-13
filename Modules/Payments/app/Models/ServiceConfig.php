<?php

namespace Modules\Payments\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;

class ServiceConfig extends Model
{
    protected $table = 'services_config';

    protected $fillable = [
        'service_name',
        'service_slug',
        'duration_minutes',
        'is_active',
        'price_1on1',
        'price_1on3_per_person',
        'price_1on5_per_person',
        'is_office_hours',
        'office_hours_subscription_price',
        'credit_cost_1on1',
        'credit_cost_1on3',
        'credit_cost_1on5',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_office_hours' => 'boolean',
        'price_1on1' => 'decimal:2',
        'price_1on3_per_person' => 'decimal:2',
        'price_1on5_per_person' => 'decimal:2',
        'office_hours_subscription_price' => 'decimal:2',
    ];

    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(Mentor::class, 'mentor_services')
            ->withPivot(['sort_order'])
            ->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}

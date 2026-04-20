<?php

namespace Modules\Payments\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;

class BookingPayment extends Model
{
    protected $table = 'booking_payments';

    protected $fillable = [
        'user_id',
        'mentor_id',
        'service_config_id',
        'booking_id',
        'mentor_availability_slot_id',
        'office_hour_session_id',
        'session_type',
        'meeting_type',
        'amount',
        'currency',
        'guest_participants',
        'request_payload',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_event_id',
        'checkout_url',
        'status',
        'failure_reason',
        'payment_completed_at',
        'booking_created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'guest_participants' => 'array',
        'request_payload' => 'array',
        'payment_completed_at' => 'datetime',
        'booking_created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceConfig::class, 'service_config_id');
    }
}

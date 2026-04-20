<?php

namespace Modules\Payments\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;

class CreditTransaction extends Model
{
    protected $table = 'credit_transactions';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'booking_id',
        'subscription_id',
        'type',
        'amount',
        'balance_after',
        'stripe_payment_id',
        'stripe_event_id',
        'stripe_subscription_id',
        'office_hours_program',
        'description',
        'performed_by',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

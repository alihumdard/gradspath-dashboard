<?php

namespace Modules\Payments\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;

class BookingRefund extends Model
{
    public const TYPE_CREDITS = 'credits';
    public const TYPE_STRIPE = 'stripe';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REQUIRES_ADMIN_REVIEW = 'requires_admin_review';

    protected $table = 'booking_refunds';

    protected $fillable = [
        'booking_id',
        'booking_payment_id',
        'mentor_payout_id',
        'student_id',
        'type',
        'status',
        'amount',
        'credits',
        'currency',
        'stripe_refund_id',
        'stripe_transfer_reversal_id',
        'failure_reason',
        'requested_at',
        'succeeded_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credits' => 'integer',
        'requested_at' => 'datetime',
        'succeeded_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingPayment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class);
    }

    public function mentorPayout(): BelongsTo
    {
        return $this->belongsTo(MentorPayout::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}

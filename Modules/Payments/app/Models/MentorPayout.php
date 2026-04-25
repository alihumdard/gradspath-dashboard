<?php

namespace Modules\Payments\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;
use Modules\Bookings\app\Models\Booking;
use Modules\Settings\app\Models\Mentor;

class MentorPayout extends Model
{
    public const STATUS_PENDING_RELEASE = 'pending_release';
    public const STATUS_READY = 'ready';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_PAID_OUT = 'paid_out';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';

    protected $table = 'mentor_payouts';

    protected $fillable = [
        'mentor_id',
        'booking_id',
        'booking_payment_id',
        'student_id',
        'stripe_account_id',
        'amount',
        'gross_amount',
        'mentor_share_amount',
        'platform_fee_amount',
        'currency',
        'calculation_rule',
        'status',
        'stripe_transfer_id',
        'stripe_balance_transaction_id',
        'failure_reason',
        'payout_date',
        'eligible_at',
        'transferred_at',
        'paid_out_at',
        'failed_at',
        'attempt_count',
        'last_attempt_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'mentor_share_amount' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'calculation_rule' => 'array',
        'payout_date' => 'datetime',
        'eligible_at' => 'datetime',
        'transferred_at' => 'datetime',
        'paid_out_at' => 'datetime',
        'failed_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'attempt_count' => 'integer',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingPayment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}

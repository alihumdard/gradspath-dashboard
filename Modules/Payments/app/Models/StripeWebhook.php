<?php

namespace Modules\Payments\app\Models;

use Illuminate\Database\Eloquent\Model;

class StripeWebhook extends Model
{
    protected $table = 'stripe_webhooks';

    protected $fillable = [
        'event_id',
        'event_type',
        'payload',
        'processed',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}

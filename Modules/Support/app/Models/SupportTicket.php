<?php

namespace Modules\Support\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\app\Models\User;

class SupportTicket extends Model
{
    protected $table = 'support_tickets';

    protected $fillable = [
        'user_id',
        'ticket_ref',
        'subject',
        'message',
        'message_raw',
        'status',
        'admin_reply',
        'handled_by',
        'replied_at',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}

<?php

namespace Modules\Payments\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\app\Models\User;

class UserCredit extends Model
{
    protected $table = 'user_credits';

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'user_id', 'user_id');
    }
}

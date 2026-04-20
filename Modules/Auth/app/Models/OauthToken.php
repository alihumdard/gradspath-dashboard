<?php

namespace Modules\Auth\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthToken extends Model
{
    protected $table = 'oauth_tokens';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

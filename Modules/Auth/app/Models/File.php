<?php

namespace Modules\Auth\app\Models;

use App\Models\User as BaseUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class File extends Model
{
    protected $fillable = [
        'user_id',
        'fileable_type',
        'fileable_id',
        'original_name',
        'stored_name',
        'path',
        'disk',
        'extension',
        'mime_type',
        'size',
        'type',
        'is_public',
        'is_deleted',
        'deleted_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
        'size' => 'integer',
    ];

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(BaseUser::class);
    }
}

<?php

namespace Modules\Auth\app\Services;

use Modules\Auth\app\Models\AdminLog;
use Modules\Auth\app\Models\User;

class AdminAuditService
{
    public function log(
        User $admin,
        string $action,
        string $targetTable,
        ?int $targetId,
        mixed $before,
        mixed $after,
        ?string $notes = null
    ): AdminLog {
        return AdminLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}

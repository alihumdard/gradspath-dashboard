<?php

namespace Modules\Auth\app\Traits;

use Modules\Auth\app\Models\User;
use Modules\Auth\app\Services\AdminAuditService;

trait LogsAdminActions
{
    protected function logAdminAction(
        User $admin,
        string $action,
        string $targetTable,
        ?int $targetId,
        mixed $before,
        mixed $after,
        ?string $notes = null
    ): void {
        app(AdminAuditService::class)->log(
            admin: $admin,
            action: $action,
            targetTable: $targetTable,
            targetId: $targetId,
            before: $before,
            after: $after,
            notes: $notes
        );
    }
}

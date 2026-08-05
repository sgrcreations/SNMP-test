<?php

namespace Modules\Authentication\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Models\AuditLog;
use Modules\Authentication\Repositories\Contracts\AuditLogRepositoryInterface;

class AuditLogService
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function log(
        string $event,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?int $userId = null,
    ): AuditLog {
        return $this->auditLogs->record([
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }
}

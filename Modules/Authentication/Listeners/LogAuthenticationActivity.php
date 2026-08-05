<?php

namespace Modules\Authentication\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Modules\Authentication\Services\AuditLogService;

class LogAuthenticationActivity
{
    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {}

    public function handleLogin(Login $event): void
    {
        $this->auditLogs->log(
            event: 'auth.login',
            auditable: $event->user,
            description: 'User logged in successfully.',
            userId: $event->user->getAuthIdentifier(),
        );
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        $this->auditLogs->log(
            event: 'auth.logout',
            auditable: $event->user,
            description: 'User logged out.',
            userId: $event->user->getAuthIdentifier(),
        );
    }

    public function handleFailed(Failed $event): void
    {
        $this->auditLogs->log(
            event: 'auth.failed',
            description: 'Failed authentication attempt for '.($event->credentials['email'] ?? 'unknown'),
            newValues: [
                'email' => $event->credentials['email'] ?? null,
            ],
        );
    }
}

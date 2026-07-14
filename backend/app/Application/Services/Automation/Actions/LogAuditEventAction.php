<?php

declare(strict_types=1);

namespace App\Application\Services\Automation\Actions;

use App\Application\Contracts\Services\Automation\AutomationActionInterface;
use App\Application\Services\Audit\AuditLogService;

/**
 * config: {action: "log_audit_event", audit_action: string, subject_type:
 * string, subject_id: string, changes?: array<string, mixed>} — note the
 * step-dispatch key is `action` (which ActionRegistry uses to pick this
 * implementation) while the audit_logs.action value itself is the
 * separate `audit_action` key, to avoid the two colliding on the same
 * config property. Writes to the same shared audit_logs table every other
 * module uses, with actor=null (system-triggered), mirroring the "system
 * actions" case audit_logs.actor_user_id already anticipates.
 */
final class LogAuditEventAction implements AutomationActionInterface
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function name(): string
    {
        return 'log_audit_event';
    }

    public function execute(array $config, array $context): array
    {
        $this->auditLog->record(
            null,
            (string) $config['audit_action'],
            (string) $config['subject_type'],
            (string) $config['subject_id'],
            (array) ($config['changes'] ?? [])
        );

        return ['logged' => true];
    }
}

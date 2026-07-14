<?php

declare(strict_types=1);

namespace App\Application\Services\Audit;

use App\Application\Contracts\Repositories\Audit\AuditLogRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\Request;

final class AuditLogService
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogs,
        private readonly TenantContextInterface $tenantContext,
        private readonly Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $changes
     */
    public function record(
        ?Authenticatable $actor,
        string $action,
        string $subjectType,
        string $subjectId,
        array $changes = []
    ): void {
        $this->auditLogs->create([
            'tenant_id' => $this->tenantContext->tenantId(),
            'actor_user_id' => $actor?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'changes' => $changes,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    /**
     * @param  array{subject_type?: string, subject_id?: string}  $filters
     */
    public function list(array $filters = []): CursorPaginator
    {
        return $this->auditLogs->paginate($filters);
    }
}

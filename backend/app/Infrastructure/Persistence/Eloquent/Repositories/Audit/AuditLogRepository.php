<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Audit;

use App\Application\Contracts\Repositories\Audit\AuditLogRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Infrastructure\Persistence\Eloquent\Models\Audit\AuditLog;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator
    {
        return AuditLog::where('tenant_id', $this->tenantContext->tenantId())
            ->when(isset($filters['subject_type']), fn (Builder $q) => $q->where('subject_type', $filters['subject_type']))
            ->when(isset($filters['subject_id']), fn (Builder $q) => $q->where('subject_id', $filters['subject_id']))
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);
    }

    public function create(array $attributes): void
    {
        AuditLog::create(array_merge($attributes, ['created_at' => now()]));
    }
}

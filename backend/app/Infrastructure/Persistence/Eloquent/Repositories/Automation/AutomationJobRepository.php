<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Automation;

use App\Application\Contracts\Repositories\Automation\AutomationJobRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Automation\AutomationJob as AutomationJobEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Automation\AutomationJob;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class AutomationJobRepository implements AutomationJobRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForTenant(array $filters = [], int $perPage = 25): CursorPaginator
    {
        $query = $this->scoped()
            ->when(isset($filters['workflow_id']), fn (Builder $q) => $q->where('workflow_id', $filters['workflow_id']))
            ->when(isset($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($query, fn (AutomationJob $j): AutomationJobEntity => $this->toDomain($j));
    }

    public function findById(string $id): ?AutomationJobEntity
    {
        $job = $this->scoped()->find($id);

        return $job ? $this->toDomain($job) : null;
    }

    public function create(array $attributes): AutomationJobEntity
    {
        $job = AutomationJob::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($job);
    }

    public function update(string $id, array $attributes): AutomationJobEntity
    {
        $job = $this->scoped()->findOrFail($id);
        $job->fill($attributes)->save();

        return $this->toDomain($job);
    }

    private function scoped(): Builder
    {
        return AutomationJob::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(AutomationJob $job): AutomationJobEntity
    {
        return new AutomationJobEntity(
            id: $job->id,
            tenantId: $job->tenant_id,
            workflowId: $job->workflow_id,
            trigger: $job->trigger,
            status: $job->status,
            attempts: $job->attempts,
            maxAttempts: $job->max_attempts,
            context: $job->context,
            error: $job->error,
            scheduledAt: $job->scheduled_at ? \DateTimeImmutable::createFromInterface($job->scheduled_at) : null,
            startedAt: $job->started_at ? \DateTimeImmutable::createFromInterface($job->started_at) : null,
            finishedAt: $job->finished_at ? \DateTimeImmutable::createFromInterface($job->finished_at) : null,
            createdAt: \DateTimeImmutable::createFromInterface($job->created_at),
            updatedAt: \DateTimeImmutable::createFromInterface($job->updated_at),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Automation;

use App\Application\Contracts\Repositories\Automation\WorkflowRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Automation\Workflow as WorkflowEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Automation\Workflow;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class WorkflowRepository implements WorkflowRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForTenant(int $perPage = 25): CursorPaginator
    {
        $query = $this->scoped()
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($query, fn (Workflow $w): WorkflowEntity => $this->toDomain($w));
    }

    public function findById(string $id): ?WorkflowEntity
    {
        $workflow = $this->scoped()->find($id);

        return $workflow ? $this->toDomain($workflow) : null;
    }

    public function findActiveByEventTrigger(string $eventKey): array
    {
        return $this->scoped()
            ->where('status', 'active')
            ->whereHas('steps', function (Builder $q) use ($eventKey): void {
                $q->where('type', 'trigger')
                    ->where('config->kind', 'event')
                    ->where('config->event', $eventKey);
            })
            ->get()
            ->map(fn (Workflow $w): WorkflowEntity => $this->toDomain($w))
            ->all();
    }

    public function findActiveScheduled(): array
    {
        return $this->scoped()
            ->where('status', 'active')
            ->whereHas('steps', function (Builder $q): void {
                $q->where('type', 'trigger')->where('config->kind', 'schedule');
            })
            ->get()
            ->map(fn (Workflow $w): WorkflowEntity => $this->toDomain($w))
            ->all();
    }

    public function create(array $attributes): WorkflowEntity
    {
        $workflow = Workflow::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($workflow);
    }

    public function update(string $id, array $attributes): WorkflowEntity
    {
        $workflow = $this->scoped()->findOrFail($id);
        $workflow->fill($attributes)->save();

        return $this->toDomain($workflow);
    }

    public function delete(string $id): void
    {
        $this->scoped()->findOrFail($id)->delete();
    }

    private function scoped(): Builder
    {
        return Workflow::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(Workflow $workflow): WorkflowEntity
    {
        return new WorkflowEntity(
            id: $workflow->id,
            tenantId: $workflow->tenant_id,
            createdByUserId: $workflow->created_by_user_id,
            name: $workflow->name,
            description: $workflow->description,
            status: $workflow->status,
            lastTriggeredAt: $workflow->last_triggered_at ? \DateTimeImmutable::createFromInterface($workflow->last_triggered_at) : null,
            createdAt: \DateTimeImmutable::createFromInterface($workflow->created_at),
            updatedAt: \DateTimeImmutable::createFromInterface($workflow->updated_at),
        );
    }
}

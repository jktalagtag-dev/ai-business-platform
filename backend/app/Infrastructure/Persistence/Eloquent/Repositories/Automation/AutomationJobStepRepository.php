<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Automation;

use App\Application\Contracts\Repositories\Automation\AutomationJobStepRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Automation\AutomationJobStep as AutomationJobStepEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Automation\AutomationJobStep;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class AutomationJobStepRepository implements AutomationJobStepRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForJob(string $automationJobId, int $perPage = 50): CursorPaginator
    {
        $query = AutomationJobStep::where('tenant_id', $this->tenantContext->tenantId())
            ->where('automation_job_id', $automationJobId)
            ->orderBy('step_order')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($query, fn (AutomationJobStep $s): AutomationJobStepEntity => $this->toDomain($s));
    }

    public function create(array $attributes): AutomationJobStepEntity
    {
        $step = AutomationJobStep::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($step);
    }

    public function update(string $id, array $attributes): AutomationJobStepEntity
    {
        $step = AutomationJobStep::where('tenant_id', $this->tenantContext->tenantId())->findOrFail($id);
        $step->fill($attributes)->save();

        return $this->toDomain($step);
    }

    private function toDomain(AutomationJobStep $step): AutomationJobStepEntity
    {
        return new AutomationJobStepEntity(
            id: $step->id,
            tenantId: $step->tenant_id,
            automationJobId: $step->automation_job_id,
            workflowStepId: $step->workflow_step_id,
            stepOrder: $step->step_order,
            type: $step->type,
            status: $step->status,
            output: $step->output,
            error: $step->error,
            startedAt: $step->started_at ? \DateTimeImmutable::createFromInterface($step->started_at) : null,
            finishedAt: $step->finished_at ? \DateTimeImmutable::createFromInterface($step->finished_at) : null,
            createdAt: \DateTimeImmutable::createFromInterface($step->created_at),
        );
    }
}

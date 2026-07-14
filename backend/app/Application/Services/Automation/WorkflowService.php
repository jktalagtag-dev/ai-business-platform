<?php

declare(strict_types=1);

namespace App\Application\Services\Automation;

use App\Application\Contracts\Repositories\Automation\WorkflowRepositoryInterface;
use App\Application\Contracts\Repositories\Automation\WorkflowStepRepositoryInterface;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Automation\Workflow;
use App\Domain\Automation\WorkflowStep;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class WorkflowService
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly WorkflowStepRepositoryInterface $steps,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(Authenticatable $actor, int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Workflow::class);

        return $this->workflows->paginateForTenant($perPage);
    }

    public function find(Authenticatable $actor, string $id): Workflow
    {
        $workflow = $this->workflows->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $workflow);

        return $workflow;
    }

    /**
     * @return array<int, WorkflowStep>
     */
    public function steps(Authenticatable $actor, string $workflowId): array
    {
        $workflow = $this->find($actor, $workflowId);

        return $this->steps->forWorkflow($workflow->id);
    }

    /**
     * @param  list<array{type: string, config: array<string, mixed>}>  $steps
     */
    public function create(Authenticatable $actor, string $name, ?string $description, array $steps): Workflow
    {
        Gate::forUser($actor)->authorize('create', Workflow::class);

        $workflow = $this->workflows->create([
            'created_by_user_id' => $actor->getAuthIdentifier(),
            'name' => $name,
            'description' => $description,
            'status' => 'draft',
            'last_triggered_at' => null,
        ]);

        $this->steps->createMany($workflow->id, $steps);

        $this->auditLog->record($actor, 'automation.workflow_created', 'workflow', $workflow->id, [
            'name' => $name,
            'step_count' => count($steps),
        ]);

        return $workflow;
    }

    public function activate(Authenticatable $actor, string $id): Workflow
    {
        $workflow = $this->workflows->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $workflow);

        $updated = $this->workflows->update($id, ['status' => 'active']);

        $this->auditLog->record($actor, 'automation.workflow_activated', 'workflow', $id, []);

        return $updated;
    }

    public function pause(Authenticatable $actor, string $id): Workflow
    {
        $workflow = $this->workflows->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $workflow);

        $updated = $this->workflows->update($id, ['status' => 'paused']);

        $this->auditLog->record($actor, 'automation.workflow_paused', 'workflow', $id, []);

        return $updated;
    }

    public function delete(Authenticatable $actor, string $id): void
    {
        $workflow = $this->workflows->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('delete', $workflow);

        $this->workflows->delete($id);

        $this->auditLog->record($actor, 'automation.workflow_deleted', 'workflow', $id, []);
    }
}

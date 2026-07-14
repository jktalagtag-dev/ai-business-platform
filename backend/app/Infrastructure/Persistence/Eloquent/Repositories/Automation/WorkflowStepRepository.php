<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Automation;

use App\Application\Contracts\Repositories\Automation\WorkflowStepRepositoryInterface;
use App\Domain\Automation\WorkflowStep as WorkflowStepEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Automation\WorkflowStep;

final class WorkflowStepRepository implements WorkflowStepRepositoryInterface
{
    public function forWorkflow(string $workflowId): array
    {
        return WorkflowStep::where('workflow_id', $workflowId)
            ->orderBy('step_order')
            ->get()
            ->map(fn (WorkflowStep $s): WorkflowStepEntity => $this->toDomain($s))
            ->all();
    }

    public function createMany(string $workflowId, array $steps): void
    {
        foreach ($steps as $index => $step) {
            WorkflowStep::create([
                'workflow_id' => $workflowId,
                'step_order' => $index,
                'type' => $step['type'],
                'config' => $step['config'],
            ]);
        }
    }

    private function toDomain(WorkflowStep $step): WorkflowStepEntity
    {
        return new WorkflowStepEntity(
            id: $step->id,
            workflowId: $step->workflow_id,
            stepOrder: $step->step_order,
            type: $step->type,
            config: $step->config,
        );
    }
}

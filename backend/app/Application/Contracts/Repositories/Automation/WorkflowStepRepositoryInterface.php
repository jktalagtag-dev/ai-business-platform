<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Automation;

use App\Domain\Automation\WorkflowStep;

interface WorkflowStepRepositoryInterface
{
    /**
     * Ordered by step_order.
     *
     * @return list<WorkflowStep>
     */
    public function forWorkflow(string $workflowId): array;

    /**
     * @param  list<array{type: string, config: array<string, mixed>}>  $steps  in order, step_order assigned by position
     */
    public function createMany(string $workflowId, array $steps): void;
}

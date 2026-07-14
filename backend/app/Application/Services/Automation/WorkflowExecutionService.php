<?php

declare(strict_types=1);

namespace App\Application\Services\Automation;

use App\Application\Contracts\Repositories\Automation\AutomationJobStepRepositoryInterface;
use App\Application\Contracts\Repositories\Automation\WorkflowStepRepositoryInterface;
use App\Domain\Automation\AutomationJob;
use App\Domain\Automation\ConditionEvaluator;
use App\Domain\Automation\PlaceholderResolver;
use App\Domain\Automation\WorkflowStep;

/**
 * Runs one automation_jobs row through its workflow's ordered steps,
 * recording a per-step automation_job_steps row for each. Only manages
 * step-level state — job-level status (running/succeeded/failed/retry) is
 * the caller's (ExecuteWorkflowJob's) responsibility, since only it knows
 * whether a failure should trigger a retry.
 *
 * A condition step that evaluates false is not a failure — it's a
 * legitimate "this automation correctly did nothing" outcome, so it
 * short-circuits the remaining steps as 'skipped' and returns normally.
 */
final class WorkflowExecutionService
{
    public function __construct(
        private readonly WorkflowStepRepositoryInterface $workflowSteps,
        private readonly AutomationJobStepRepositoryInterface $jobSteps,
        private readonly ActionRegistry $actions,
    ) {}

    public function run(AutomationJob $job): void
    {
        $steps = $this->workflowSteps->forWorkflow($job->workflowId);
        $context = $job->context ?? [];

        foreach ($steps as $index => $step) {
            if ($step->isTrigger()) {
                continue;
            }

            $jobStep = $this->jobSteps->create([
                'automation_job_id' => $job->id,
                'workflow_step_id' => $step->id,
                'step_order' => $step->stepOrder,
                'type' => $step->type,
                'status' => 'running',
                'started_at' => now(),
            ]);

            if ($step->isCondition()) {
                $passed = ConditionEvaluator::evaluate($step->config, $context);

                $this->jobSteps->update($jobStep->id, [
                    'status' => $passed ? 'succeeded' : 'skipped',
                    'output' => ['passed' => $passed],
                    'finished_at' => now(),
                ]);

                if (! $passed) {
                    $this->skipRemaining($job, array_slice($steps, $index + 1));

                    return;
                }

                continue;
            }

            // Action step.
            try {
                $renderedConfig = PlaceholderResolver::renderConfig($step->config, $context);
                $output = $this->actions->execute((string) $step->config['action'], $renderedConfig, $context);

                $this->jobSteps->update($jobStep->id, [
                    'status' => 'succeeded',
                    'output' => $output,
                    'finished_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $this->jobSteps->update($jobStep->id, [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'finished_at' => now(),
                ]);

                throw $e;
            }
        }
    }

    /**
     * @param  list<WorkflowStep>  $remainingSteps
     */
    private function skipRemaining(AutomationJob $job, array $remainingSteps): void
    {
        foreach ($remainingSteps as $step) {
            if ($step->isTrigger()) {
                continue;
            }

            $this->jobSteps->create([
                'automation_job_id' => $job->id,
                'workflow_step_id' => $step->id,
                'step_order' => $step->stepOrder,
                'type' => $step->type,
                'status' => 'skipped',
                'started_at' => now(),
                'finished_at' => now(),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Services\Automation;

use App\Application\Contracts\Repositories\Automation\AutomationJobRepositoryInterface;
use App\Application\Contracts\Repositories\Automation\WorkflowRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Application\Jobs\Automation\ExecuteWorkflowJob;

/**
 * The event-driven core: called from AutomationEventSubscriber whenever
 * one of the events this engine understands fires. Runs within the same
 * request that raised the event, so TenantContextInterface already
 * reflects the right tenant — one automation_jobs row (status: queued) is
 * created per matching active workflow, and ExecuteWorkflowJob is
 * dispatched to run it on the `automation` queue.
 */
final class WorkflowMatchingService
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows,
        private readonly AutomationJobRepositoryInterface $jobs,
        private readonly TenantContextInterface $tenantContext,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function handle(string $triggerKey, array $context): void
    {
        $tenantId = $this->tenantContext->tenantId();

        foreach ($this->workflows->findActiveByEventTrigger($triggerKey) as $workflow) {
            $job = $this->jobs->create([
                'workflow_id' => $workflow->id,
                'trigger' => $triggerKey,
                'status' => 'queued',
                'attempts' => 0,
                'max_attempts' => (int) config('automation.default_max_attempts'),
                'context' => $context,
                'scheduled_at' => now(),
            ]);

            ExecuteWorkflowJob::dispatch($tenantId, $job->id);
        }
    }
}

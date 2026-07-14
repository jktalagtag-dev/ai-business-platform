<?php

declare(strict_types=1);

namespace App\Application\Jobs\Automation;

use App\Application\Contracts\Repositories\Automation\AutomationJobRepositoryInterface;
use App\Application\Services\Audit\AuditLogService;
use App\Application\Services\Automation\WorkflowExecutionService;
use App\Http\Support\RequestTenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs one automation_jobs row. Retry handling is self-managed rather than
 * Laravel's built-in $tries/backoff(): automation_jobs.attempts/
 * max_attempts is the single source of truth (visible via the API, and
 * settable per job), so on a step failure this job re-dispatches itself
 * with an increasing delay until max_attempts is reached, then marks the
 * job permanently 'failed'. Laravel's own queue-level retry still applies
 * as a fallback for failures outside this try/catch (e.g. a DB connection
 * error while loading the job) — those aren't this job's business logic
 * to reason about.
 *
 * Like every other queue job in this codebase, tenant context is carried
 * explicitly and set on RequestTenantContext as the first line of handle(),
 * since queue workers run outside any HTTP request.
 */
final class ExecuteWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $tenantId,
        private readonly string $automationJobId,
    ) {
        $this->onQueue('automation');
    }

    public function handle(
        RequestTenantContext $tenantContext,
        AutomationJobRepositoryInterface $jobs,
        WorkflowExecutionService $executor,
        AuditLogService $auditLog,
    ): void {
        $tenantContext->setTenantId($this->tenantId);

        $job = $jobs->findById($this->automationJobId);

        if ($job === null) {
            return;
        }

        $attemptNumber = $job->attempts + 1;

        $jobs->update($job->id, [
            'status' => 'running',
            'started_at' => $job->startedAt ?? now(),
            'attempts' => $attemptNumber,
        ]);

        try {
            $executor->run($job);

            $jobs->update($job->id, [
                'status' => 'succeeded',
                'finished_at' => now(),
                'error' => null,
            ]);

            $auditLog->record(null, 'automation.job_succeeded', 'automation_job', $job->id, [
                'workflow_id' => $job->workflowId,
                'attempts' => $attemptNumber,
            ]);
        } catch (\Throwable $e) {
            if ($attemptNumber >= $job->maxAttempts) {
                $jobs->update($job->id, [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'finished_at' => now(),
                ]);

                $auditLog->record(null, 'automation.job_failed', 'automation_job', $job->id, [
                    'workflow_id' => $job->workflowId,
                    'error' => $e->getMessage(),
                    'attempts' => $attemptNumber,
                ]);

                return;
            }

            $jobs->update($job->id, [
                'status' => 'queued',
                'error' => $e->getMessage(),
            ]);

            self::dispatch($this->tenantId, $this->automationJobId)
                ->delay(now()->addSeconds(10 * $attemptNumber));
        }
    }
}

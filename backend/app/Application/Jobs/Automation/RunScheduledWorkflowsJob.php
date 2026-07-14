<?php

declare(strict_types=1);

namespace App\Application\Jobs\Automation;

use App\Application\Contracts\Repositories\Automation\AutomationJobRepositoryInterface;
use App\Application\Contracts\Repositories\Automation\WorkflowRepositoryInterface;
use App\Application\Contracts\Repositories\Automation\WorkflowStepRepositoryInterface;
use App\Http\Support\RequestTenantContext;
use App\Infrastructure\Persistence\Eloquent\Models\Tenant;
use Cron\CronExpression;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled every minute (see routes/console.php), not event-driven —
 * mirrors SlaMonitoringJob's precedent of a system-wide job iterating
 * every tenant itself, since there is no single tenant/request driving it.
 * For each tenant, finds active schedule-triggered workflows and fires any
 * whose cron expression is due right now, de-duping via
 * workflows.last_triggered_at so a workflow due for this minute isn't
 * fired twice if this job's own tick timing wobbles.
 */
final class RunScheduledWorkflowsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('automation');
    }

    public function handle(
        RequestTenantContext $tenantContext,
        WorkflowRepositoryInterface $workflows,
        WorkflowStepRepositoryInterface $workflowSteps,
        AutomationJobRepositoryInterface $jobs,
    ): void {
        $now = now();

        foreach (Tenant::pluck('id') as $tenantId) {
            $tenantContext->setTenantId($tenantId);

            foreach ($workflows->findActiveScheduled() as $workflow) {
                if ($workflow->lastTriggeredAt !== null && $workflow->lastTriggeredAt->getTimestamp() > $now->copy()->subSeconds(55)->getTimestamp()) {
                    continue;
                }

                $triggerStep = null;

                foreach ($workflowSteps->forWorkflow($workflow->id) as $step) {
                    if ($step->isTrigger()) {
                        $triggerStep = $step;

                        break;
                    }
                }

                $cron = $triggerStep?->config['cron'] ?? null;

                if (! is_string($cron)) {
                    continue;
                }

                try {
                    $due = (new CronExpression($cron))->isDue($now->toDateTimeString());
                } catch (\Throwable) {
                    continue;
                }

                if (! $due) {
                    continue;
                }

                $workflows->update($workflow->id, ['last_triggered_at' => $now]);

                $job = $jobs->create([
                    'workflow_id' => $workflow->id,
                    'trigger' => 'schedule',
                    'status' => 'queued',
                    'attempts' => 0,
                    'max_attempts' => (int) config('automation.default_max_attempts'),
                    'context' => [],
                    'scheduled_at' => $now,
                ]);

                ExecuteWorkflowJob::dispatch($tenantId, $job->id);
            }
        }
    }
}

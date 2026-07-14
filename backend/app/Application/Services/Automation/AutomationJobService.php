<?php

declare(strict_types=1);

namespace App\Application\Services\Automation;

use App\Application\Contracts\Repositories\Automation\AutomationJobRepositoryInterface;
use App\Application\Contracts\Repositories\Automation\AutomationJobStepRepositoryInterface;
use App\Application\Jobs\Automation\ExecuteWorkflowJob;
use App\Domain\Automation\AutomationJob;
use App\Domain\Shared\Exceptions\InvalidAutomationJobRetryException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class AutomationJobService
{
    public function __construct(
        private readonly AutomationJobRepositoryInterface $jobs,
        private readonly AutomationJobStepRepositoryInterface $jobSteps,
    ) {}

    /**
     * @param  array{workflow_id?: string, status?: string}  $filters
     */
    public function list(Authenticatable $actor, array $filters = [], int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', AutomationJob::class);

        return $this->jobs->paginateForTenant($filters, $perPage);
    }

    public function find(Authenticatable $actor, string $id): AutomationJob
    {
        $job = $this->jobs->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $job);

        return $job;
    }

    public function steps(Authenticatable $actor, string $id, int $perPage = 50): CursorPaginator
    {
        $job = $this->find($actor, $id);

        return $this->jobSteps->paginateForJob($job->id, $perPage);
    }

    public function retry(Authenticatable $actor, string $id): AutomationJob
    {
        $job = $this->jobs->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('retry', $job);

        if (! $job->isRetryable()) {
            throw new InvalidAutomationJobRetryException($job->status);
        }

        $this->jobs->update($id, [
            'status' => 'queued',
            'attempts' => 0,
            'error' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);

        ExecuteWorkflowJob::dispatch($job->tenantId, $job->id);

        // Re-fetch rather than returning the entity captured above: under
        // the real (async) queue this still reflects 'queued', exactly as
        // it should; under the 'sync' driver used in tests, the job has
        // already run by the time dispatch() returns, so this picks up its
        // final status instead of a now-stale in-memory snapshot (the same
        // fix applied to DocumentService::upload() in the Knowledge Base
        // module).
        return $this->jobs->findById($id) ?? throw new ModelNotFoundException;
    }
}

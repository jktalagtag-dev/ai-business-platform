<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Automation;

use App\Domain\Automation\Workflow;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface WorkflowRepositoryInterface
{
    public function paginateForTenant(int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?Workflow;

    /**
     * Active workflows in the current tenant whose trigger step's config
     * matches the given event key (config->>'kind' = 'event' and
     * config->>'event' = $eventKey).
     *
     * @return list<Workflow>
     */
    public function findActiveByEventTrigger(string $eventKey): array;

    /**
     * Active, schedule-triggered workflows for the current tenant context.
     * Scoped like every other method here — RunScheduledWorkflowsJob is the
     * one that iterates every tenant, setting tenant context before each
     * call, mirroring SlaMonitoringJob's precedent.
     *
     * @return list<Workflow>
     */
    public function findActiveScheduled(): array;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Workflow;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $id, array $attributes): Workflow;

    public function delete(string $id): void;
}

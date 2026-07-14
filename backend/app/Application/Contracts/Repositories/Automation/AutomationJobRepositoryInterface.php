<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Automation;

use App\Domain\Automation\AutomationJob;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface AutomationJobRepositoryInterface
{
    /**
     * @param  array{workflow_id?: string, status?: string}  $filters
     */
    public function paginateForTenant(array $filters = [], int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?AutomationJob;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AutomationJob;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $id, array $attributes): AutomationJob;
}

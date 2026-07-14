<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Automation;

use App\Domain\Automation\AutomationJobStep;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface AutomationJobStepRepositoryInterface
{
    public function paginateForJob(string $automationJobId, int $perPage = 50): CursorPaginator;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AutomationJobStep;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $id, array $attributes): AutomationJobStep;
}

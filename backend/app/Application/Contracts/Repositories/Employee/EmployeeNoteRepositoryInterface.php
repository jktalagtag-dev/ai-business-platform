<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Employee;

use App\Domain\Employee\EmployeeNote;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface EmployeeNoteRepositoryInterface
{
    public function paginateForEmployee(string $employeeId, int $perPage = 25): CursorPaginator;

    /**
     * @param  array{employee_id: string, author_user_id: ?string, note: string}  $attributes
     */
    public function create(array $attributes): EmployeeNote;
}

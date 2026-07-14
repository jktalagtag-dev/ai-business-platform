<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Employee;

use App\Domain\Employee\Department;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface DepartmentRepositoryInterface
{
    public function paginate(int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?Department;

    public function nameExistsUnderParent(?string $parentDepartmentId, string $name, ?string $exceptId = null): bool;

    /**
     * @param  array{parent_department_id: ?string, manager_employee_id: ?string, name: string, description: ?string}  $attributes
     */
    public function create(array $attributes): Department;

    /**
     * @param  array{parent_department_id: ?string, manager_employee_id: ?string, name: string, description: ?string}  $attributes
     */
    public function update(string $id, array $attributes): Department;

    public function delete(string $id): void;

    public function isManagedBy(string $departmentId, string $employeeId): bool;

    /**
     * @return list<string>
     */
    public function managedDepartmentIds(string $employeeId): array;
}

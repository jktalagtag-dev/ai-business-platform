<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Employee;

use App\Domain\Employee\Employee;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface EmployeeRepositoryInterface
{
    /**
     * @param  array{department_id?: string, department_id_in?: list<string>, position_id?: string, employment_status?: string, manager_employee_id?: string, search?: string, sort?: string, direction?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?Employee;

    public function findByUserId(string $userId): ?Employee;

    public function emailExists(string $email, ?string $exceptId = null): bool;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Employee;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $id, array $attributes): Employee;

    public function updateAvatarPath(string $id, string $path): Employee;

    public function delete(string $id): void;
}

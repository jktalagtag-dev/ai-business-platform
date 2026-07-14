<?php

declare(strict_types=1);

namespace App\Application\Services\Employee;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\DTOs\Employee\CreateDepartmentData;
use App\Application\DTOs\Employee\UpdateDepartmentData;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Employee\Department;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $departments,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(Authenticatable $actor, int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Department::class);

        return $this->departments->paginate($perPage);
    }

    public function find(Authenticatable $actor, string $id): Department
    {
        $department = $this->departments->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $department);

        return $department;
    }

    public function create(Authenticatable $actor, CreateDepartmentData $data): Department
    {
        Gate::forUser($actor)->authorize('create', Department::class);

        $department = $this->departments->create([
            'parent_department_id' => $data->parentDepartmentId,
            'manager_employee_id' => $data->managerEmployeeId,
            'name' => $data->name,
            'description' => $data->description,
        ]);

        $this->auditLog->record($actor, 'department.created', 'department', $department->id, [
            'name' => $department->name,
        ]);

        return $department;
    }

    public function update(Authenticatable $actor, string $id, UpdateDepartmentData $data): Department
    {
        $existing = $this->departments->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $existing);

        $department = $this->departments->update($id, [
            'parent_department_id' => $data->parentDepartmentId,
            'manager_employee_id' => $data->managerEmployeeId,
            'name' => $data->name,
            'description' => $data->description,
        ]);

        $this->auditLog->record($actor, 'department.updated', 'department', $department->id, [
            'before' => ['name' => $existing->name, 'manager_employee_id' => $existing->managerEmployeeId],
            'after' => ['name' => $department->name, 'manager_employee_id' => $department->managerEmployeeId],
        ]);

        return $department;
    }

    public function delete(Authenticatable $actor, string $id): void
    {
        $existing = $this->departments->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('delete', $existing);

        $this->departments->delete($id);

        $this->auditLog->record($actor, 'department.deleted', 'department', $existing->id, [
            'name' => $existing->name,
        ]);
    }
}

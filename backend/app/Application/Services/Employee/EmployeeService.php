<?php

declare(strict_types=1);

namespace App\Application\Services\Employee;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Services\EmployeeCodeGeneratorInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Application\DTOs\Employee\CreateEmployeeData;
use App\Application\DTOs\Employee\UpdateEmployeeData;
use App\Application\Events\Employee\EmployeeArchived;
use App\Application\Events\Employee\EmployeeCreated;
use App\Application\Events\Employee\EmployeeUpdated;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Employee\Employee;
use App\Domain\Shared\Exceptions\InvalidManagerAssignmentException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class EmployeeService
{
    /** Fields only an employees.manage-capable actor may change (see UpdateEmployeeData::restrictedFields). */
    private const ADMIN_ONLY_AUDIT_ACTIONS = [
        'department_id' => 'employee.department_changed',
        'position_id' => 'employee.position_changed',
        'manager_employee_id' => 'employee.manager_changed',
        'employment_status' => 'employee.status_changed',
    ];

    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly DepartmentRepositoryInterface $departments,
        private readonly EmployeeCodeGeneratorInterface $codeGenerator,
        private readonly TenantContextInterface $tenantContext,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array{department_id?: string, position_id?: string, employment_status?: string, manager_employee_id?: string, search?: string, sort?: string, direction?: string}  $filters
     */
    public function list(Authenticatable $actor, array $filters = [], int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Employee::class);

        if (Gate::forUser($actor)->denies('viewAllEmployees', Employee::class)) {
            $self = $this->employees->findByUserId($actor->getAuthIdentifier());
            $managedDepartmentIds = $self ? $this->departments->managedDepartmentIds($self->id) : [];

            $filters['department_id_in'] = $managedDepartmentIds;
        }

        return $this->employees->paginate($filters, $perPage);
    }

    public function find(Authenticatable $actor, string $id): Employee
    {
        $employee = $this->employees->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $employee);

        return $employee;
    }

    public function findSelf(Authenticatable $actor): Employee
    {
        return $this->employees->findByUserId($actor->getAuthIdentifier()) ?? throw new ModelNotFoundException;
    }

    public function create(Authenticatable $actor, CreateEmployeeData $data): Employee
    {
        Gate::forUser($actor)->authorize('create', Employee::class);

        $employeeNumber = $this->codeGenerator->next($this->tenantContext->tenantId());

        $employee = $this->employees->create([
            'user_id' => $data->userId,
            'employee_number' => $employeeNumber,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'phone' => $data->phone,
            'department_id' => $data->departmentId,
            'position_id' => $data->positionId,
            'manager_employee_id' => $data->managerEmployeeId,
            'employment_type' => $data->employmentType,
            'employment_status' => $data->employmentStatus,
            'hire_date' => $data->hireDate,
            'termination_date' => null,
            'address' => $data->address,
            'emergency_contact' => $data->emergencyContact?->toArray(),
            'bio' => $data->bio,
        ]);

        $this->auditLog->record($actor, 'employee.created', 'employee', $employee->id, [
            'employee_number' => $employee->employeeNumber,
            'name' => $employee->fullName(),
        ]);

        EmployeeCreated::dispatch($employee);

        return $employee;
    }

    public function update(Authenticatable $actor, string $id, UpdateEmployeeData $data): Employee
    {
        $existing = $this->employees->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $existing);

        $isManageCapable = Gate::forUser($actor)->allows('create', Employee::class);

        if (! $isManageCapable) {
            $this->assertNoRestrictedFieldChanges($existing, $data);
        }

        if ($data->managerEmployeeId === $id) {
            throw new InvalidManagerAssignmentException('An employee cannot be their own manager.');
        }

        $employee = $this->employees->update($id, [
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'phone' => $data->phone,
            'department_id' => $data->departmentId,
            'position_id' => $data->positionId,
            'manager_employee_id' => $data->managerEmployeeId,
            'employment_type' => $data->employmentType,
            'employment_status' => $data->employmentStatus,
            'hire_date' => $data->hireDate,
            'termination_date' => $data->terminationDate,
            'address' => $data->address,
            'emergency_contact' => $data->emergencyContact?->toArray(),
            'bio' => $data->bio,
        ]);

        $changes = $this->diff($existing, $employee);

        $this->auditLog->record($actor, 'employee.updated', 'employee', $employee->id, $changes);

        foreach (self::ADMIN_ONLY_AUDIT_ACTIONS as $field => $action) {
            if (array_key_exists($field, $changes)) {
                $this->auditLog->record($actor, $action, 'employee', $employee->id, $changes[$field]);
            }
        }

        if (array_key_exists('first_name', $changes) || array_key_exists('last_name', $changes)
            || array_key_exists('phone', $changes) || array_key_exists('address', $changes)
            || array_key_exists('emergency_contact', $changes) || array_key_exists('bio', $changes)) {
            $this->auditLog->record($actor, 'employee.profile_updated', 'employee', $employee->id, []);
        }

        EmployeeUpdated::dispatch($employee, $changes);

        return $employee;
    }

    public function uploadAvatar(Authenticatable $actor, string $id, string $storedPath): Employee
    {
        $existing = $this->employees->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $existing);

        $employee = $this->employees->updateAvatarPath($id, $storedPath);

        $this->auditLog->record($actor, 'employee.avatar_updated', 'employee', $employee->id, []);

        return $employee;
    }

    public function delete(Authenticatable $actor, string $id): void
    {
        $existing = $this->employees->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('delete', $existing);

        $this->employees->delete($id);

        $this->auditLog->record($actor, 'employee.archived', 'employee', $existing->id, [
            'employee_number' => $existing->employeeNumber,
            'name' => $existing->fullName(),
        ]);

        EmployeeArchived::dispatch($existing);
    }

    private function assertNoRestrictedFieldChanges(Employee $existing, UpdateEmployeeData $data): void
    {
        $current = [
            'department_id' => $existing->departmentId,
            'position_id' => $existing->positionId,
            'manager_employee_id' => $existing->managerEmployeeId,
            'employment_type' => $existing->employmentType,
            'employment_status' => $existing->employmentStatus,
            'hire_date' => $existing->hireDate,
            'termination_date' => $existing->terminationDate,
        ];

        foreach ($data->restrictedFields() as $field => $value) {
            if ($current[$field] !== $value) {
                throw new AuthorizationException(
                    "You are not authorized to change '{$field}'. Only Admin or HR can update employment details."
                );
            }
        }
    }

    /**
     * @return array<string, array{before: mixed, after: mixed}>
     */
    private function diff(Employee $before, Employee $after): array
    {
        $beforeArray = [
            'first_name' => $before->firstName,
            'last_name' => $before->lastName,
            'email' => $before->email,
            'phone' => $before->phone,
            'department_id' => $before->departmentId,
            'position_id' => $before->positionId,
            'manager_employee_id' => $before->managerEmployeeId,
            'employment_type' => $before->employmentType,
            'employment_status' => $before->employmentStatus,
            'hire_date' => $before->hireDate,
            'termination_date' => $before->terminationDate,
            'address' => $before->address,
            'emergency_contact' => $before->emergencyContact?->toArray(),
            'bio' => $before->bio,
        ];

        $afterArray = [
            'first_name' => $after->firstName,
            'last_name' => $after->lastName,
            'email' => $after->email,
            'phone' => $after->phone,
            'department_id' => $after->departmentId,
            'position_id' => $after->positionId,
            'manager_employee_id' => $after->managerEmployeeId,
            'employment_type' => $after->employmentType,
            'employment_status' => $after->employmentStatus,
            'hire_date' => $after->hireDate,
            'termination_date' => $after->terminationDate,
            'address' => $after->address,
            'emergency_contact' => $after->emergencyContact?->toArray(),
            'bio' => $after->bio,
        ];

        $changes = [];
        foreach ($beforeArray as $field => $value) {
            if ($value !== $afterArray[$field]) {
                $changes[$field] = ['before' => $value, 'after' => $afterArray[$field]];
            }
        }

        return $changes;
    }
}

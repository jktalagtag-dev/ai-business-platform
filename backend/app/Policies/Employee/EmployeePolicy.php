<?php

declare(strict_types=1);

namespace App\Policies\Employee;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Domain\Employee\Employee;
use App\Policies\Concerns\AuthorizesViaTokenAbilities;
use Illuminate\Contracts\Auth\Authenticatable;

final class EmployeePolicy
{
    use AuthorizesViaTokenAbilities;

    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly DepartmentRepositoryInterface $departments,
    ) {}

    /**
     * Anyone who can see the full directory, or who manages at least one
     * department, may hit the list endpoint — EmployeeService::list() then
     * scopes the query itself for the department-manager case.
     */
    public function viewAny(Authenticatable $user): bool
    {
        return $this->viewAllEmployees($user) || $this->managesAnyDepartment($user);
    }

    /**
     * True only for the broad, unscoped view permission (Owner/Admin/HR).
     * Used by the Service to decide whether the list query needs
     * department-scoping for a manager, distinct from viewAny's broader
     * "can hit the endpoint at all" check.
     */
    public function viewAllEmployees(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'employees.view');
    }

    public function view(Authenticatable $user, Employee $employee): bool
    {
        return $this->viewAllEmployees($user)
            || $this->isSelf($user, $employee)
            || $this->managesDepartment($user, $employee->departmentId);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->hasAbility($user, 'employees.manage');
    }

    /**
     * employees.manage (Admin/HR/Owner) can update any field; a self-service
     * update is also allowed here, but EmployeeService::update() rejects it
     * if it touches employment-only fields (department/position/manager/
     * status/dates) — Employees can only update their own profile.
     */
    public function update(Authenticatable $user, Employee $employee): bool
    {
        return $this->hasAbility($user, 'employees.manage') || $this->isSelf($user, $employee);
    }

    public function delete(Authenticatable $user, Employee $employee): bool
    {
        return $this->hasAbility($user, 'employees.manage');
    }

    public function addNote(Authenticatable $user, Employee $employee): bool
    {
        return $this->hasAbility($user, 'employees.manage')
            || $this->managesDepartment($user, $employee->departmentId);
    }

    private function isSelf(Authenticatable $user, Employee $employee): bool
    {
        return $employee->userId !== null && $employee->userId === $user->getAuthIdentifier();
    }

    private function managesDepartment(Authenticatable $user, ?string $departmentId): bool
    {
        if ($departmentId === null) {
            return false;
        }

        $self = $this->employees->findByUserId($user->getAuthIdentifier());

        return $self !== null && $this->departments->isManagedBy($departmentId, $self->id);
    }

    private function managesAnyDepartment(Authenticatable $user): bool
    {
        $self = $this->employees->findByUserId($user->getAuthIdentifier());

        return $self !== null && $this->departments->managedDepartmentIds($self->id) !== [];
    }
}

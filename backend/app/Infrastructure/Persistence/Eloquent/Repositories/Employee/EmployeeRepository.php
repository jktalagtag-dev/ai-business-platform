<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Employee;

use App\Application\Contracts\Repositories\Employee\EmployeeRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Employee\EmergencyContact;
use App\Domain\Employee\Employee as EmployeeEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Employee\Employee;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class EmployeeRepository implements EmployeeRepositoryInterface
{
    private const SORTABLE_COLUMNS = ['first_name', 'last_name', 'hire_date', 'created_at'];

    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator
    {
        $sort = in_array($filters['sort'] ?? 'created_at', self::SORTABLE_COLUMNS, true)
            ? $filters['sort'] ?? 'created_at'
            : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $paginator = $this->scoped()
            ->when(isset($filters['department_id']), fn (Builder $q) => $q->where('department_id', $filters['department_id']))
            ->when(
                isset($filters['department_id_in']),
                fn (Builder $q) => $q->whereIn('department_id', $filters['department_id_in'])
            )
            ->when(isset($filters['position_id']), fn (Builder $q) => $q->where('position_id', $filters['position_id']))
            ->when(
                isset($filters['employment_status']),
                fn (Builder $q) => $q->where('employment_status', $filters['employment_status'])
            )
            ->when(
                isset($filters['manager_employee_id']),
                fn (Builder $q) => $q->where('manager_employee_id', $filters['manager_employee_id'])
            )
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(fn (Builder $q) => $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('employee_number', 'like', $term));
            })
            ->orderBy($sort, $direction)
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($paginator, fn (Employee $e): EmployeeEntity => $this->toDomain($e));
    }

    public function findById(string $id): ?EmployeeEntity
    {
        $employee = $this->scoped()->find($id);

        return $employee ? $this->toDomain($employee) : null;
    }

    public function findByUserId(string $userId): ?EmployeeEntity
    {
        $employee = $this->scoped()->where('user_id', $userId)->first();

        return $employee ? $this->toDomain($employee) : null;
    }

    public function emailExists(string $email, ?string $exceptId = null): bool
    {
        return $this->scoped()
            ->where('email', $email)
            ->when($exceptId, fn (Builder $q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function create(array $attributes): EmployeeEntity
    {
        $employee = Employee::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($employee);
    }

    public function update(string $id, array $attributes): EmployeeEntity
    {
        $employee = $this->scoped()->findOrFail($id);
        $employee->fill($attributes)->save();

        return $this->toDomain($employee);
    }

    public function updateAvatarPath(string $id, string $path): EmployeeEntity
    {
        $employee = $this->scoped()->findOrFail($id);
        $employee->forceFill(['avatar_path' => $path])->save();

        return $this->toDomain($employee);
    }

    public function delete(string $id): void
    {
        $this->scoped()->findOrFail($id)->delete();
    }

    private function scoped(): Builder
    {
        return Employee::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(Employee $employee): EmployeeEntity
    {
        return new EmployeeEntity(
            id: $employee->id,
            tenantId: $employee->tenant_id,
            userId: $employee->user_id,
            employeeNumber: $employee->employee_number,
            firstName: $employee->first_name,
            lastName: $employee->last_name,
            email: $employee->email,
            phone: $employee->phone,
            departmentId: $employee->department_id,
            positionId: $employee->position_id,
            managerEmployeeId: $employee->manager_employee_id,
            employmentType: $employee->employment_type,
            employmentStatus: $employee->employment_status,
            hireDate: $employee->hire_date->toDateString(),
            terminationDate: $employee->termination_date?->toDateString(),
            address: $employee->address,
            emergencyContact: $employee->emergency_contact ? EmergencyContact::fromArray($employee->emergency_contact) : null,
            avatarPath: $employee->avatar_path,
            bio: $employee->bio,
        );
    }
}

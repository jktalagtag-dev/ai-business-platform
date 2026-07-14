<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Employee;

use App\Application\Contracts\Repositories\Employee\DepartmentRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Employee\Department as DepartmentEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Employee\Department;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginate(int $perPage = 25): CursorPaginator
    {
        return CachedCursorPaginator::wrap(
            $this->scoped()->orderBy('name')->cursorPaginate($perPage),
            fn (Department $d): DepartmentEntity => $this->toDomain($d)
        );
    }

    public function findById(string $id): ?DepartmentEntity
    {
        $department = $this->scoped()->find($id);

        return $department ? $this->toDomain($department) : null;
    }

    public function nameExistsUnderParent(?string $parentDepartmentId, string $name, ?string $exceptId = null): bool
    {
        return $this->scoped()
            ->where('parent_department_id', $parentDepartmentId)
            ->where('name', $name)
            ->when($exceptId, fn (Builder $q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function create(array $attributes): DepartmentEntity
    {
        $department = Department::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($department);
    }

    public function update(string $id, array $attributes): DepartmentEntity
    {
        $department = $this->scoped()->findOrFail($id);
        $department->fill($attributes)->save();

        return $this->toDomain($department);
    }

    public function delete(string $id): void
    {
        $this->scoped()->findOrFail($id)->delete();
    }

    public function isManagedBy(string $departmentId, string $employeeId): bool
    {
        return $this->scoped()
            ->where('id', $departmentId)
            ->where('manager_employee_id', $employeeId)
            ->exists();
    }

    public function managedDepartmentIds(string $employeeId): array
    {
        return $this->scoped()
            ->where('manager_employee_id', $employeeId)
            ->pluck('id')
            ->all();
    }

    private function scoped(): Builder
    {
        return Department::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(Department $department): DepartmentEntity
    {
        return new DepartmentEntity(
            id: $department->id,
            tenantId: $department->tenant_id,
            parentDepartmentId: $department->parent_department_id,
            managerEmployeeId: $department->manager_employee_id,
            name: $department->name,
            description: $department->description,
        );
    }
}

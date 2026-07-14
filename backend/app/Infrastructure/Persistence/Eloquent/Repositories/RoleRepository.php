<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Contracts\Repositories\RoleRepositoryInterface;
use App\Domain\Rbac\Role as RoleEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Role;

final class RoleRepository implements RoleRepositoryInterface
{
    public function findSystemRoleByName(string $name): ?RoleEntity
    {
        $role = Role::whereNull('tenant_id')
            ->where('is_system', true)
            ->where('name', $name)
            ->with('permissions')
            ->first();

        return $role ? $this->toDomain($role) : null;
    }

    public function findById(string $id): ?RoleEntity
    {
        $role = Role::with('permissions')->find($id);

        return $role ? $this->toDomain($role) : null;
    }

    public function all(): array
    {
        return Role::whereNull('tenant_id')
            ->where('is_system', true)
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): RoleEntity => $this->toDomain($role))
            ->all();
    }

    private function toDomain(Role $role): RoleEntity
    {
        return new RoleEntity(
            id: $role->id,
            tenantId: $role->tenant_id,
            name: $role->name,
            isSystem: $role->is_system,
            permissionKeys: $role->permissions->pluck('key')->all(),
        );
    }
}

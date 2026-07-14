<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use App\Domain\Rbac\Role as RoleEntity;
use App\Domain\Rbac\TenantMembership;
use App\Infrastructure\Persistence\Eloquent\Models\TenantUser;

final class TenantUserRepository implements TenantUserRepositoryInterface
{
    public function create(string $tenantId, string $userId, string $roleId): TenantMembership
    {
        $membership = TenantUser::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'status' => 'active',
        ]);

        $membership->load('role.permissions', 'tenant');

        return $this->toDomain($membership);
    }

    public function findMembershipsForUser(string $userId): array
    {
        return TenantUser::where('user_id', $userId)
            ->where('status', 'active')
            ->with(['role.permissions', 'tenant'])
            ->get()
            ->map(fn (TenantUser $m): TenantMembership => $this->toDomain($m))
            ->all();
    }

    public function findMembership(string $tenantId, string $userId): ?TenantMembership
    {
        $membership = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->with(['role.permissions', 'tenant'])
            ->first();

        return $membership ? $this->toDomain($membership) : null;
    }

    private function toDomain(TenantUser $membership): TenantMembership
    {
        return new TenantMembership(
            id: $membership->id,
            tenantId: $membership->tenant_id,
            tenantName: $membership->tenant->name,
            tenantSlug: $membership->tenant->slug,
            userId: $membership->user_id,
            role: new RoleEntity(
                id: $membership->role->id,
                tenantId: $membership->role->tenant_id,
                name: $membership->role->name,
                isSystem: $membership->role->is_system,
                permissionKeys: $membership->role->permissions->pluck('key')->all(),
            ),
            status: $membership->status,
        );
    }
}

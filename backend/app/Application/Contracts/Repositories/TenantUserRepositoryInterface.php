<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories;

use App\Domain\Rbac\TenantMembership;

interface TenantUserRepositoryInterface
{
    public function create(string $tenantId, string $userId, string $roleId): TenantMembership;

    /**
     * @return list<TenantMembership>
     */
    public function findMembershipsForUser(string $userId): array;

    public function findMembership(string $tenantId, string $userId): ?TenantMembership;
}

<?php

declare(strict_types=1);

namespace App\Domain\Rbac;

final class TenantMembership
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $tenantName,
        public readonly string $tenantSlug,
        public readonly string $userId,
        public readonly Role $role,
        public readonly string $status,
    ) {}
}

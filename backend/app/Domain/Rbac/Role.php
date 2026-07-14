<?php

declare(strict_types=1);

namespace App\Domain\Rbac;

final class Role
{
    /**
     * @param  list<string>  $permissionKeys
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $tenantId,
        public readonly string $name,
        public readonly bool $isSystem,
        public readonly array $permissionKeys = [],
    ) {}

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->permissionKeys, true);
    }
}

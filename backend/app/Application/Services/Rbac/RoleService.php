<?php

declare(strict_types=1);

namespace App\Application\Services\Rbac;

use App\Application\Contracts\Repositories\RoleRepositoryInterface;
use App\Domain\Rbac\Role;

final class RoleService
{
    public function __construct(private readonly RoleRepositoryInterface $roles) {}

    /**
     * @return list<Role>
     */
    public function listAssignableRoles(): array
    {
        return $this->roles->all();
    }
}

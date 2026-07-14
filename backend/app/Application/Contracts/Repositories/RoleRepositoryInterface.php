<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories;

use App\Domain\Rbac\Role;

interface RoleRepositoryInterface
{
    public function findSystemRoleByName(string $name): ?Role;

    public function findById(string $id): ?Role;

    /**
     * @return list<Role>
     */
    public function all(): array;
}

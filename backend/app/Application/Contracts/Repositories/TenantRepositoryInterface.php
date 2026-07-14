<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories;

use App\Domain\Rbac\Tenant;

interface TenantRepositoryInterface
{
    public function create(string $name, string $slug): Tenant;

    public function findBySlug(string $slug): ?Tenant;

    public function slugExists(string $slug): bool;
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Contracts\Repositories\TenantRepositoryInterface;
use App\Domain\Rbac\Tenant as TenantEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Tenant;

final class TenantRepository implements TenantRepositoryInterface
{
    public function create(string $name, string $slug): TenantEntity
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'plan' => 'free',
        ]);

        return $this->toDomain($tenant);
    }

    public function findBySlug(string $slug): ?TenantEntity
    {
        $tenant = Tenant::where('slug', $slug)->first();

        return $tenant ? $this->toDomain($tenant) : null;
    }

    public function slugExists(string $slug): bool
    {
        return Tenant::where('slug', $slug)->exists();
    }

    private function toDomain(Tenant $tenant): TenantEntity
    {
        return new TenantEntity(
            id: $tenant->id,
            name: $tenant->name,
            slug: $tenant->slug,
            plan: $tenant->plan,
        );
    }
}

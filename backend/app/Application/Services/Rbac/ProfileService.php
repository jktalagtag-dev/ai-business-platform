<?php

declare(strict_types=1);

namespace App\Application\Services\Rbac;

use App\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use App\Application\Contracts\Repositories\UserRepositoryInterface;
use App\Application\DTOs\Rbac\UpdateProfileData;
use App\Domain\Rbac\TenantMembership;
use Illuminate\Contracts\Auth\Authenticatable;

final class ProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TenantUserRepositoryInterface $tenantUsers,
    ) {}

    /**
     * @return array{user: Authenticatable, memberships: list<TenantMembership>}
     */
    public function show(Authenticatable $user): array
    {
        return [
            'user' => $user,
            'memberships' => $this->tenantUsers->findMembershipsForUser($user->getAuthIdentifier()),
        ];
    }

    public function update(Authenticatable $user, UpdateProfileData $data): Authenticatable
    {
        return $this->users->updateProfile($user->getAuthIdentifier(), [
            'name' => $data->name,
            'email' => $data->email,
        ]);
    }
}

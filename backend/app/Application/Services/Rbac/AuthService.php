<?php

declare(strict_types=1);

namespace App\Application\Services\Rbac;

use App\Application\Contracts\Repositories\RoleRepositoryInterface;
use App\Application\Contracts\Repositories\TenantRepositoryInterface;
use App\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use App\Application\Contracts\Repositories\UserRepositoryInterface;
use App\Application\Contracts\Services\PasswordResetterInterface;
use App\Application\Contracts\Services\TokenIssuerInterface;
use App\Application\DTOs\Rbac\LoginData;
use App\Application\DTOs\Rbac\RegisterData;
use App\Application\DTOs\Rbac\ResetPasswordData;
use App\Domain\Rbac\TenantMembership;
use App\Domain\Shared\Exceptions\AmbiguousTenantException;
use App\Domain\Shared\Exceptions\EmailAlreadyRegisteredException;
use App\Domain\Shared\Exceptions\InvalidCredentialsException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AuthService
{
    private const OWNER_ROLE = 'Owner';

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TenantRepositoryInterface $tenants,
        private readonly RoleRepositoryInterface $roles,
        private readonly TenantUserRepositoryInterface $tenantUsers,
        private readonly TokenIssuerInterface $tokens,
        private readonly PasswordResetterInterface $passwordResetter,
    ) {}

    /**
     * @return array{token: string, user: Authenticatable, membership: TenantMembership}
     */
    public function register(RegisterData $data): array
    {
        if ($this->users->existsByEmail($data->email)) {
            throw new EmailAlreadyRegisteredException;
        }

        $user = $this->users->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        $tenant = $this->tenants->create($data->tenantName, $this->generateUniqueSlug($data->tenantName));
        $ownerRole = $this->roles->findSystemRoleByName(self::OWNER_ROLE);

        $membership = $this->tenantUsers->create($tenant->id, $user->getAuthIdentifier(), $ownerRole->id);

        $token = $this->tokens->issueToken($user, 'auth-token', $this->abilitiesFor($membership));

        return ['token' => $token, 'user' => $user, 'membership' => $membership];
    }

    /**
     * @return array{token: string, user: Authenticatable, membership: TenantMembership}
     */
    public function login(LoginData $data): array
    {
        $user = $this->users->findByEmail($data->email);

        if (! $user || ! Hash::check($data->password, $user->getAuthPassword())) {
            throw new InvalidCredentialsException;
        }

        $memberships = $this->tenantUsers->findMembershipsForUser($user->getAuthIdentifier());

        $membership = match (true) {
            $data->tenantSlug !== null => $this->findMembershipBySlug($memberships, $data->tenantSlug),
            count($memberships) === 1 => $memberships[0],
            count($memberships) > 1 => throw new AmbiguousTenantException(
                array_map(
                    static fn (TenantMembership $m): array => ['slug' => $m->tenantSlug, 'name' => $m->tenantName],
                    $memberships
                )
            ),
            default => throw new InvalidCredentialsException,
        };

        $token = $this->tokens->issueToken($user, 'auth-token', $this->abilitiesFor($membership));

        return ['token' => $token, 'user' => $user, 'membership' => $membership];
    }

    public function logout(Authenticatable $user, string $tokenId): void
    {
        $this->tokens->revokeToken($user, $tokenId);
    }

    public function sendPasswordResetLink(string $email): void
    {
        // The returned status is intentionally ignored so the API response
        // never reveals whether an account exists for this email address.
        $this->passwordResetter->sendResetLink($email);
    }

    public function resetPassword(ResetPasswordData $data): void
    {
        $this->passwordResetter->reset($data->email, $data->token, $data->password);
    }

    /**
     * @param  list<TenantMembership>  $memberships
     */
    private function findMembershipBySlug(array $memberships, string $slug): TenantMembership
    {
        foreach ($memberships as $membership) {
            if ($membership->tenantSlug === $slug) {
                return $membership;
            }
        }

        throw new InvalidCredentialsException;
    }

    /**
     * @return list<string>
     */
    private function abilitiesFor(TenantMembership $membership): array
    {
        return array_merge(
            ['role:'.Str::lower($membership->role->name), 'tenant:'.$membership->tenantId],
            $membership->role->permissionKeys
        );
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $suffix = 1;

        while ($this->tenants->slugExists($slug)) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}

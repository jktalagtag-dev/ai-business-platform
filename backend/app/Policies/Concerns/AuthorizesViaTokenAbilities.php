<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * All Inventory Policies check the same thing: does the caller's current
 * Sanctum token carry the relevant permission ability? Abilities are
 * granted per-role at token issuance (AuthService) from the role's
 * permissions in the database (RolePermissionSeeder), so this is a DB-driven
 * RBAC check, not a hardcoded role-name check — adding a new permission to a
 * role takes effect for that role's *next* login, consistent with the same
 * trade-off already accepted by EnsureUserHasRole for role-based routes.
 */
trait AuthorizesViaTokenAbilities
{
    private function hasAbility(Authenticatable $user, string $ability): bool
    {
        $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        return (bool) $token?->can($ability);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Http\Support\RequestTenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves which tenant the current request is scoped to from the
 * authenticated Sanctum token's `tenant:{id}` ability (set at issuance by
 * AuthService), and binds it into RequestTenantContext for the duration of
 * the request. Applied only to routes whose data is tenant-owned (e.g.
 * inventory), not to the tenant-agnostic auth/profile routes.
 */
final class ResolveTenant
{
    public function __construct(private readonly RequestTenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        $tenantAbility = collect($token?->abilities ?? [])->first(
            static fn (string $ability): bool => str_starts_with($ability, 'tenant:')
        );

        if (! $tenantAbility) {
            return ApiResponse::error(
                'forbidden',
                'This token is not scoped to a tenant.',
                status: 403
            );
        }

        $this->tenantContext->setTenantId(substr($tenantAbility, strlen('tenant:')));

        return $next($request);
    }
}

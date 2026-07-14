<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token) {
            return ApiResponse::error('unauthenticated', 'Authentication required.', status: 401);
        }

        foreach ($roles as $role) {
            if ($token->can('role:'.Str::lower($role))) {
                return $next($request);
            }
        }

        return ApiResponse::error(
            'forbidden',
            'You do not have the required role to perform this action.',
            status: 403
        );
    }
}

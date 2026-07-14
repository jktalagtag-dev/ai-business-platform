<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Application\Contracts\Services\TokenIssuerInterface;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

final class SanctumTokenIssuer implements TokenIssuerInterface
{
    public function issueToken(Authenticatable $user, string $tokenName, array $abilities): string
    {
        /** @var User $user */
        return $user->createToken($tokenName, $abilities)->plainTextToken;
    }

    public function revokeToken(Authenticatable $user, string $tokenId): void
    {
        /** @var User $user */
        $user->tokens()->where('id', $tokenId)->delete();
    }

    public function revokeAllTokens(Authenticatable $user): void
    {
        /** @var User $user */
        $user->tokens()->delete();
    }
}

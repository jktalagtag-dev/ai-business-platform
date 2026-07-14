<?php

declare(strict_types=1);

namespace App\Application\Contracts\Services;

use Illuminate\Contracts\Auth\Authenticatable;

interface TokenIssuerInterface
{
    /**
     * @param  list<string>  $abilities
     */
    public function issueToken(Authenticatable $user, string $tokenName, array $abilities): string;

    public function revokeToken(Authenticatable $user, string $tokenId): void;

    public function revokeAllTokens(Authenticatable $user): void;
}

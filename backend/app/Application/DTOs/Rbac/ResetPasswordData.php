<?php

declare(strict_types=1);

namespace App\Application\DTOs\Rbac;

final class ResetPasswordData
{
    public function __construct(
        public readonly string $email,
        public readonly string $token,
        public readonly string $password,
    ) {}
}

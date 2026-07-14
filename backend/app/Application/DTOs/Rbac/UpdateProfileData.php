<?php

declare(strict_types=1);

namespace App\Application\DTOs\Rbac;

final class UpdateProfileData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

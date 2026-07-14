<?php

declare(strict_types=1);

namespace App\Domain\Rbac;

final class Permission
{
    public function __construct(
        public readonly string $id,
        public readonly string $key,
        public readonly ?string $description,
    ) {}
}

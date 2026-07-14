<?php

declare(strict_types=1);

namespace App\Domain\Rbac;

final class Tenant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $plan,
    ) {}
}

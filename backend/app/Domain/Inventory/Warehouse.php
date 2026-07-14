<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

final class Warehouse
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
    ) {}
}

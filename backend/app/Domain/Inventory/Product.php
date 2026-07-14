<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

final class Product
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly ?string $categoryId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $unitPrice,
        public readonly string $costPrice,
        public readonly bool $isActive,
    ) {}
}

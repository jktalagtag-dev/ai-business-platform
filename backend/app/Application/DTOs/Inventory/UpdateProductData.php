<?php

declare(strict_types=1);

namespace App\Application\DTOs\Inventory;

final class UpdateProductData
{
    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $categoryId,
        public readonly string $unitPrice,
        public readonly string $costPrice,
        public readonly bool $isActive,
    ) {}
}

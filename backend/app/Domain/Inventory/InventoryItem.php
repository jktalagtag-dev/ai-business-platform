<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

final class InventoryItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly string $productSku,
        public readonly string $productName,
        public readonly string $warehouseId,
        public readonly int $quantityOnHand,
        public readonly int $quantityReserved,
        public readonly int $reorderPoint,
        public readonly int $reorderQuantity,
    ) {}

    public function isLowStock(): bool
    {
        return $this->quantityOnHand <= $this->reorderPoint;
    }
}

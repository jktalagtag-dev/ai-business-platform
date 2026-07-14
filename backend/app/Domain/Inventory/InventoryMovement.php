<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

final class InventoryMovement
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $inventoryItemId,
        public readonly string $movementType,
        public readonly int $quantity,
        public readonly ?string $reason,
        public readonly ?string $createdByUserId,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}

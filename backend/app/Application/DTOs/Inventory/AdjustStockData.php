<?php

declare(strict_types=1);

namespace App\Application\DTOs\Inventory;

final class AdjustStockData
{
    public function __construct(
        public readonly int $quantity,
        public readonly string $movementType,
        public readonly ?string $reason,
    ) {}
}

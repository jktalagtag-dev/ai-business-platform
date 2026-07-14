<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Inventory;

use App\Domain\Inventory\InventoryItem;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface InventoryItemRepositoryInterface
{
    /**
     * @param  array{low_stock?: bool}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator;

    public function findByProductId(string $productId): ?InventoryItem;

    public function createForProduct(string $productId, string $warehouseId): InventoryItem;

    public function adjustQuantity(string $inventoryItemId, int $delta): InventoryItem;
}

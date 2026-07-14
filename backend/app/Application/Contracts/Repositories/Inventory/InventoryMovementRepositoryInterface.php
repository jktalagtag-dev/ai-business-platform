<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Inventory;

use App\Domain\Inventory\InventoryMovement;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface InventoryMovementRepositoryInterface
{
    public function paginateForInventoryItem(string $inventoryItemId, int $perPage = 25): CursorPaginator;

    /**
     * @param  array{inventory_item_id: string, movement_type: string, quantity: int, reason: ?string, created_by_user_id: ?string}  $attributes
     */
    public function create(array $attributes): InventoryMovement;
}

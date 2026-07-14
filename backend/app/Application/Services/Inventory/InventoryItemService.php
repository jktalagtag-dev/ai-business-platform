<?php

declare(strict_types=1);

namespace App\Application\Services\Inventory;

use App\Application\Contracts\Repositories\Inventory\InventoryItemRepositoryInterface;
use App\Application\Contracts\Repositories\Inventory\InventoryMovementRepositoryInterface;
use App\Application\DTOs\Inventory\AdjustStockData;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Inventory\InventoryItem;
use App\Domain\Shared\Exceptions\InsufficientStockException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class InventoryItemService
{
    public function __construct(
        private readonly InventoryItemRepositoryInterface $inventoryItems,
        private readonly InventoryMovementRepositoryInterface $movements,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array{low_stock?: bool}  $filters
     */
    public function list(Authenticatable $actor, array $filters = [], int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', InventoryItem::class);

        return $this->inventoryItems->paginate($filters, $perPage);
    }

    public function findByProductId(Authenticatable $actor, string $productId): InventoryItem
    {
        $item = $this->inventoryItems->findByProductId($productId) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $item);

        return $item;
    }

    public function movements(Authenticatable $actor, string $productId, int $perPage = 25): CursorPaginator
    {
        $item = $this->findByProductId($actor, $productId);

        return $this->movements->paginateForInventoryItem($item->id, $perPage);
    }

    public function adjust(Authenticatable $actor, string $productId, AdjustStockData $data): InventoryItem
    {
        $item = $this->inventoryItems->findByProductId($productId) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $item);

        $resulting = $item->quantityOnHand + $data->quantity;

        if ($resulting < 0) {
            throw new InsufficientStockException($item->productSku, $item->quantityOnHand, abs($data->quantity));
        }

        $updated = $this->inventoryItems->adjustQuantity($item->id, $data->quantity);

        $movement = $this->movements->create([
            'inventory_item_id' => $item->id,
            'movement_type' => $data->movementType,
            'quantity' => $data->quantity,
            'reason' => $data->reason,
            'created_by_user_id' => $actor->getAuthIdentifier(),
        ]);

        $this->auditLog->record($actor, 'inventory.adjusted', 'inventory_item', $updated->id, [
            'movement_id' => $movement->id,
            'movement_type' => $data->movementType,
            'quantity_delta' => $data->quantity,
            'quantity_before' => $item->quantityOnHand,
            'quantity_after' => $updated->quantityOnHand,
            'reason' => $data->reason,
        ]);

        return $updated;
    }
}

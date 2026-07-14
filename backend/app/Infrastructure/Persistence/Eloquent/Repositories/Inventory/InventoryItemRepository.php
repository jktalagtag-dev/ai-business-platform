<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Inventory;

use App\Application\Contracts\Repositories\Inventory\InventoryItemRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Inventory\InventoryItem as InventoryItemEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Inventory\InventoryItem;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class InventoryItemRepository implements InventoryItemRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator
    {
        $paginator = $this->scoped()
            ->when(
                $filters['low_stock'] ?? false,
                fn (Builder $q) => $q->whereColumn('quantity_on_hand', '<=', 'reorder_point')
            )
            ->orderBy('id')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($paginator, fn (InventoryItem $item): InventoryItemEntity => $this->toDomain($item));
    }

    public function findByProductId(string $productId): ?InventoryItemEntity
    {
        $item = $this->scoped()->where('product_id', $productId)->first();

        return $item ? $this->toDomain($item) : null;
    }

    public function createForProduct(string $productId, string $warehouseId): InventoryItemEntity
    {
        $item = InventoryItem::create([
            'tenant_id' => $this->tenantContext->tenantId(),
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'reorder_point' => 0,
            'reorder_quantity' => 0,
        ]);

        return $this->toDomain($item);
    }

    public function adjustQuantity(string $inventoryItemId, int $delta): InventoryItemEntity
    {
        $item = DB::transaction(function () use ($inventoryItemId, $delta): InventoryItem {
            $item = $this->scoped()->lockForUpdate()->findOrFail($inventoryItemId);
            $item->increment('quantity_on_hand', $delta);

            return $item;
        });

        return $this->toDomain($item->fresh(['product']));
    }

    private function scoped(): Builder
    {
        return InventoryItem::with('product')->where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(InventoryItem $item): InventoryItemEntity
    {
        return new InventoryItemEntity(
            id: $item->id,
            tenantId: $item->tenant_id,
            productId: $item->product_id,
            productSku: $item->product->sku,
            productName: $item->product->name,
            warehouseId: $item->warehouse_id,
            quantityOnHand: $item->quantity_on_hand,
            quantityReserved: $item->quantity_reserved,
            reorderPoint: $item->reorder_point,
            reorderQuantity: $item->reorder_quantity,
        );
    }
}

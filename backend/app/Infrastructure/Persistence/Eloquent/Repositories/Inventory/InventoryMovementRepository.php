<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Inventory;

use App\Application\Contracts\Repositories\Inventory\InventoryMovementRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Inventory\InventoryMovement as InventoryMovementEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Inventory\InventoryMovement;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class InventoryMovementRepository implements InventoryMovementRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginateForInventoryItem(string $inventoryItemId, int $perPage = 25): CursorPaginator
    {
        $paginator = InventoryMovement::where('tenant_id', $this->tenantContext->tenantId())
            ->where('inventory_item_id', $inventoryItemId)
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap(
            $paginator,
            fn (InventoryMovement $movement): InventoryMovementEntity => $this->toDomain($movement)
        );
    }

    public function create(array $attributes): InventoryMovementEntity
    {
        $movement = InventoryMovement::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
            'created_at' => now(),
        ]));

        return $this->toDomain($movement);
    }

    private function toDomain(InventoryMovement $movement): InventoryMovementEntity
    {
        return new InventoryMovementEntity(
            id: $movement->id,
            tenantId: $movement->tenant_id,
            inventoryItemId: $movement->inventory_item_id,
            movementType: $movement->movement_type,
            quantity: $movement->quantity,
            reason: $movement->reason,
            createdByUserId: $movement->created_by_user_id,
            createdAt: \DateTimeImmutable::createFromInterface($movement->created_at),
        );
    }
}

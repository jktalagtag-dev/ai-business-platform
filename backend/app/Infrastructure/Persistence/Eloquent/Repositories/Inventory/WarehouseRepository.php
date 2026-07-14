<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Inventory;

use App\Application\Contracts\Repositories\Inventory\WarehouseRepositoryInterface;
use App\Domain\Inventory\Warehouse as WarehouseEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Inventory\Warehouse;

final class WarehouseRepository implements WarehouseRepositoryInterface
{
    private const DEFAULT_NAME = 'Main Warehouse';

    public function findOrCreateDefault(string $tenantId): WarehouseEntity
    {
        $warehouse = Warehouse::where('tenant_id', $tenantId)
            ->where('name', self::DEFAULT_NAME)
            ->first();

        $warehouse ??= Warehouse::create([
            'tenant_id' => $tenantId,
            'name' => self::DEFAULT_NAME,
        ]);

        return new WarehouseEntity(id: $warehouse->id, tenantId: $warehouse->tenant_id, name: $warehouse->name);
    }
}

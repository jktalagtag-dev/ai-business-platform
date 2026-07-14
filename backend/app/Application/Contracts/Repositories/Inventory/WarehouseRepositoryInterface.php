<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Inventory;

use App\Domain\Inventory\Warehouse;

interface WarehouseRepositoryInterface
{
    /**
     * Every tenant gets exactly one warehouse in this iteration of the
     * Inventory module (single-location stock tracking); the schema already
     * supports multiple, so this is the only seam that needs to change if
     * multi-warehouse support is added later.
     */
    public function findOrCreateDefault(string $tenantId): Warehouse;
}

<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Inventory;

use App\Domain\Inventory\Product;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface ProductRepositoryInterface
{
    /**
     * @param  array{category_id?: string, is_active?: bool, search?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?Product;

    public function skuExists(string $sku, ?string $exceptId = null): bool;

    /**
     * @param  array{category_id: ?string, sku: string, name: string, description: ?string, unit_price: string, cost_price: string, is_active: bool}  $attributes
     */
    public function create(array $attributes): Product;

    /**
     * @param  array{category_id: ?string, sku: string, name: string, description: ?string, unit_price: string, cost_price: string, is_active: bool}  $attributes
     */
    public function update(string $id, array $attributes): Product;

    public function delete(string $id): void;
}

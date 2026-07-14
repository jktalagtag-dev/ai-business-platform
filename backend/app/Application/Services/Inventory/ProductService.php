<?php

declare(strict_types=1);

namespace App\Application\Services\Inventory;

use App\Application\Contracts\Repositories\Inventory\InventoryItemRepositoryInterface;
use App\Application\Contracts\Repositories\Inventory\ProductRepositoryInterface;
use App\Application\Contracts\Repositories\Inventory\WarehouseRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Application\DTOs\Inventory\CreateProductData;
use App\Application\DTOs\Inventory\UpdateProductData;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Inventory\Product;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly InventoryItemRepositoryInterface $inventoryItems,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly TenantContextInterface $tenantContext,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array{category_id?: string, is_active?: bool, search?: string}  $filters
     */
    public function list(Authenticatable $actor, array $filters = [], int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Product::class);

        return $this->products->paginate($filters, $perPage);
    }

    public function find(Authenticatable $actor, string $id): Product
    {
        $product = $this->products->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $product);

        return $product;
    }

    public function create(Authenticatable $actor, CreateProductData $data): Product
    {
        Gate::forUser($actor)->authorize('create', Product::class);

        $product = $this->products->create([
            'category_id' => $data->categoryId,
            'sku' => $data->sku,
            'name' => $data->name,
            'description' => $data->description,
            'unit_price' => $data->unitPrice,
            'cost_price' => $data->costPrice,
            'is_active' => $data->isActive,
        ]);

        // Every product gets exactly one stock record, provisioned in the
        // tenant's default warehouse, so Stock is always queryable per
        // product without a separate "create stock" step.
        $warehouse = $this->warehouses->findOrCreateDefault($this->tenantContext->tenantId());
        $this->inventoryItems->createForProduct($product->id, $warehouse->id);

        $this->auditLog->record($actor, 'product.created', 'product', $product->id, [
            'sku' => $product->sku,
            'name' => $product->name,
        ]);

        return $product;
    }

    public function update(Authenticatable $actor, string $id, UpdateProductData $data): Product
    {
        $existing = $this->products->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $existing);

        $product = $this->products->update($id, [
            'category_id' => $data->categoryId,
            'sku' => $data->sku,
            'name' => $data->name,
            'description' => $data->description,
            'unit_price' => $data->unitPrice,
            'cost_price' => $data->costPrice,
            'is_active' => $data->isActive,
        ]);

        $this->auditLog->record($actor, 'product.updated', 'product', $product->id, [
            'before' => ['sku' => $existing->sku, 'name' => $existing->name, 'unit_price' => $existing->unitPrice],
            'after' => ['sku' => $product->sku, 'name' => $product->name, 'unit_price' => $product->unitPrice],
        ]);

        return $product;
    }

    public function delete(Authenticatable $actor, string $id): void
    {
        $existing = $this->products->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('delete', $existing);

        $this->products->delete($id);

        $this->auditLog->record($actor, 'product.deleted', 'product', $existing->id, [
            'sku' => $existing->sku,
            'name' => $existing->name,
        ]);
    }
}

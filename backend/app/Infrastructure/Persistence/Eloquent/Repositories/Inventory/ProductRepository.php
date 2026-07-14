<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Inventory;

use App\Application\Contracts\Repositories\Inventory\ProductRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Inventory\Product as ProductEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Inventory\Product;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator
    {
        $paginator = $this->scoped()
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->where('category_id', $filters['category_id']))
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', $filters['is_active']))
            ->when(
                isset($filters['search']),
                fn (Builder $q) => $q->where(
                    fn (Builder $q) => $q->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('sku', 'like', '%'.$filters['search'].'%')
                )
            )
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($paginator, fn (Product $product): ProductEntity => $this->toDomain($product));
    }

    public function findById(string $id): ?ProductEntity
    {
        $product = $this->scoped()->find($id);

        return $product ? $this->toDomain($product) : null;
    }

    public function skuExists(string $sku, ?string $exceptId = null): bool
    {
        return $this->scoped()
            ->where('sku', $sku)
            ->when($exceptId, fn (Builder $q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function create(array $attributes): ProductEntity
    {
        $product = Product::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($product);
    }

    public function update(string $id, array $attributes): ProductEntity
    {
        $product = $this->scoped()->findOrFail($id);
        $product->fill($attributes)->save();

        return $this->toDomain($product);
    }

    public function delete(string $id): void
    {
        $this->scoped()->findOrFail($id)->delete();
    }

    private function scoped(): Builder
    {
        return Product::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(Product $product): ProductEntity
    {
        return new ProductEntity(
            id: $product->id,
            tenantId: $product->tenant_id,
            categoryId: $product->category_id,
            sku: $product->sku,
            name: $product->name,
            description: $product->description,
            unitPrice: (string) $product->unit_price,
            costPrice: (string) $product->cost_price,
            isActive: $product->is_active,
        );
    }
}

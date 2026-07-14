<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Inventory;

use App\Application\Contracts\Repositories\Inventory\ProductCategoryRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Inventory\ProductCategory as ProductCategoryEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Inventory\ProductCategory;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class ProductCategoryRepository implements ProductCategoryRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginate(int $perPage = 25): CursorPaginator
    {
        return CachedCursorPaginator::wrap(
            $this->scoped()->orderBy('name')->cursorPaginate($perPage),
            fn (ProductCategory $category): ProductCategoryEntity => $this->toDomain($category)
        );
    }

    public function findById(string $id): ?ProductCategoryEntity
    {
        $category = $this->scoped()->find($id);

        return $category ? $this->toDomain($category) : null;
    }

    public function nameExistsUnderParent(?string $parentCategoryId, string $name, ?string $exceptId = null): bool
    {
        return $this->scoped()
            ->where('parent_category_id', $parentCategoryId)
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    public function create(array $attributes): ProductCategoryEntity
    {
        $category = ProductCategory::create([
            'tenant_id' => $this->tenantContext->tenantId(),
            'parent_category_id' => $attributes['parent_category_id'],
            'name' => $attributes['name'],
        ]);

        return $this->toDomain($category);
    }

    public function update(string $id, array $attributes): ProductCategoryEntity
    {
        $category = $this->scoped()->findOrFail($id);
        $category->fill([
            'parent_category_id' => $attributes['parent_category_id'],
            'name' => $attributes['name'],
        ])->save();

        return $this->toDomain($category);
    }

    public function delete(string $id): void
    {
        $this->scoped()->findOrFail($id)->delete();
    }

    private function scoped(): Builder
    {
        return ProductCategory::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(ProductCategory $category): ProductCategoryEntity
    {
        return new ProductCategoryEntity(
            id: $category->id,
            tenantId: $category->tenant_id,
            parentCategoryId: $category->parent_category_id,
            name: $category->name,
        );
    }
}

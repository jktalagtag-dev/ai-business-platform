<?php

declare(strict_types=1);

namespace App\Application\Services\Inventory;

use App\Application\Contracts\Repositories\Inventory\ProductCategoryRepositoryInterface;
use App\Application\DTOs\Inventory\CreateCategoryData;
use App\Application\DTOs\Inventory\UpdateCategoryData;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Inventory\ProductCategory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class CategoryService
{
    public function __construct(
        private readonly ProductCategoryRepositoryInterface $categories,
        private readonly AuditLogService $auditLog,
    ) {}

    public function list(Authenticatable $actor, int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', ProductCategory::class);

        return $this->categories->paginate($perPage);
    }

    public function find(Authenticatable $actor, string $id): ProductCategory
    {
        $category = $this->categories->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $category);

        return $category;
    }

    public function create(Authenticatable $actor, CreateCategoryData $data): ProductCategory
    {
        Gate::forUser($actor)->authorize('create', ProductCategory::class);

        $category = $this->categories->create([
            'parent_category_id' => $data->parentCategoryId,
            'name' => $data->name,
        ]);

        $this->auditLog->record($actor, 'category.created', 'product_category', $category->id, [
            'name' => $category->name,
            'parent_category_id' => $category->parentCategoryId,
        ]);

        return $category;
    }

    public function update(Authenticatable $actor, string $id, UpdateCategoryData $data): ProductCategory
    {
        $existing = $this->categories->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $existing);

        $category = $this->categories->update($id, [
            'parent_category_id' => $data->parentCategoryId,
            'name' => $data->name,
        ]);

        $this->auditLog->record($actor, 'category.updated', 'product_category', $category->id, [
            'before' => ['name' => $existing->name, 'parent_category_id' => $existing->parentCategoryId],
            'after' => ['name' => $category->name, 'parent_category_id' => $category->parentCategoryId],
        ]);

        return $category;
    }

    public function delete(Authenticatable $actor, string $id): void
    {
        $existing = $this->categories->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('delete', $existing);

        $this->categories->delete($id);

        $this->auditLog->record($actor, 'category.deleted', 'product_category', $existing->id, [
            'name' => $existing->name,
        ]);
    }
}

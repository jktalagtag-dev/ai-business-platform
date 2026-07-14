<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Inventory;

use App\Domain\Inventory\ProductCategory;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface ProductCategoryRepositoryInterface
{
    public function paginate(int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?ProductCategory;

    public function nameExistsUnderParent(?string $parentCategoryId, string $name, ?string $exceptId = null): bool;

    /**
     * @param  array{parent_category_id: ?string, name: string}  $attributes
     */
    public function create(array $attributes): ProductCategory;

    /**
     * @param  array{parent_category_id: ?string, name: string}  $attributes
     */
    public function update(string $id, array $attributes): ProductCategory;

    public function delete(string $id): void;
}

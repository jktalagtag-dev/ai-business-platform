<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories\Inventory;

use App\Domain\Inventory\Supplier;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface SupplierRepositoryInterface
{
    /**
     * @param  array{status?: string, search?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator;

    public function findById(string $id): ?Supplier;

    /**
     * @param  array{name: string, contact_email: ?string, contact_phone: ?string, address: ?array<string, mixed>, status: string}  $attributes
     */
    public function create(array $attributes): Supplier;

    /**
     * @param  array{name: string, contact_email: ?string, contact_phone: ?string, address: ?array<string, mixed>, status: string}  $attributes
     */
    public function update(string $id, array $attributes): Supplier;

    public function delete(string $id): void;
}

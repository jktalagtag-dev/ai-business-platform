<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories\Inventory;

use App\Application\Contracts\Repositories\Inventory\SupplierRepositoryInterface;
use App\Application\Contracts\Services\TenantContextInterface;
use App\Domain\Inventory\Supplier as SupplierEntity;
use App\Http\Support\CachedCursorPaginator;
use App\Infrastructure\Persistence\Eloquent\Models\Inventory\Supplier;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;

final class SupplierRepository implements SupplierRepositoryInterface
{
    public function __construct(private readonly TenantContextInterface $tenantContext) {}

    public function paginate(array $filters = [], int $perPage = 25): CursorPaginator
    {
        $paginator = $this->scoped()
            ->when(isset($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(isset($filters['search']), fn (Builder $q) => $q->where('name', 'like', '%'.$filters['search'].'%'))
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage);

        return CachedCursorPaginator::wrap($paginator, fn (Supplier $supplier): SupplierEntity => $this->toDomain($supplier));
    }

    public function findById(string $id): ?SupplierEntity
    {
        $supplier = $this->scoped()->find($id);

        return $supplier ? $this->toDomain($supplier) : null;
    }

    public function create(array $attributes): SupplierEntity
    {
        $supplier = Supplier::create(array_merge($attributes, [
            'tenant_id' => $this->tenantContext->tenantId(),
        ]));

        return $this->toDomain($supplier);
    }

    public function update(string $id, array $attributes): SupplierEntity
    {
        $supplier = $this->scoped()->findOrFail($id);
        $supplier->fill($attributes)->save();

        return $this->toDomain($supplier);
    }

    public function delete(string $id): void
    {
        $this->scoped()->findOrFail($id)->delete();
    }

    private function scoped(): Builder
    {
        return Supplier::where('tenant_id', $this->tenantContext->tenantId());
    }

    private function toDomain(Supplier $supplier): SupplierEntity
    {
        return new SupplierEntity(
            id: $supplier->id,
            tenantId: $supplier->tenant_id,
            name: $supplier->name,
            contactEmail: $supplier->contact_email,
            contactPhone: $supplier->contact_phone,
            address: $supplier->address,
            status: $supplier->status,
        );
    }
}

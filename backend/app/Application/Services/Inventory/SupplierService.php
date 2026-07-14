<?php

declare(strict_types=1);

namespace App\Application\Services\Inventory;

use App\Application\Contracts\Repositories\Inventory\SupplierRepositoryInterface;
use App\Application\DTOs\Inventory\CreateSupplierData;
use App\Application\DTOs\Inventory\UpdateSupplierData;
use App\Application\Services\Audit\AuditLogService;
use App\Domain\Inventory\Supplier;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class SupplierService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array{status?: string, search?: string}  $filters
     */
    public function list(Authenticatable $actor, array $filters = [], int $perPage = 25): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Supplier::class);

        return $this->suppliers->paginate($filters, $perPage);
    }

    public function find(Authenticatable $actor, string $id): Supplier
    {
        $supplier = $this->suppliers->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('view', $supplier);

        return $supplier;
    }

    public function create(Authenticatable $actor, CreateSupplierData $data): Supplier
    {
        Gate::forUser($actor)->authorize('create', Supplier::class);

        $supplier = $this->suppliers->create([
            'name' => $data->name,
            'contact_email' => $data->contactEmail,
            'contact_phone' => $data->contactPhone,
            'address' => $data->address,
            'status' => $data->status,
        ]);

        $this->auditLog->record($actor, 'supplier.created', 'supplier', $supplier->id, [
            'name' => $supplier->name,
            'status' => $supplier->status,
        ]);

        return $supplier;
    }

    public function update(Authenticatable $actor, string $id, UpdateSupplierData $data): Supplier
    {
        $existing = $this->suppliers->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('update', $existing);

        $supplier = $this->suppliers->update($id, [
            'name' => $data->name,
            'contact_email' => $data->contactEmail,
            'contact_phone' => $data->contactPhone,
            'address' => $data->address,
            'status' => $data->status,
        ]);

        $this->auditLog->record($actor, 'supplier.updated', 'supplier', $supplier->id, [
            'before' => ['name' => $existing->name, 'status' => $existing->status],
            'after' => ['name' => $supplier->name, 'status' => $supplier->status],
        ]);

        return $supplier;
    }

    public function delete(Authenticatable $actor, string $id): void
    {
        $existing = $this->suppliers->findById($id) ?? throw new ModelNotFoundException;

        Gate::forUser($actor)->authorize('delete', $existing);

        $this->suppliers->delete($id);

        $this->auditLog->record($actor, 'supplier.deleted', 'supplier', $existing->id, [
            'name' => $existing->name,
        ]);
    }
}

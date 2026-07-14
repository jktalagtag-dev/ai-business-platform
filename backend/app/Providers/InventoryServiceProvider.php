<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Contracts\Repositories\Audit\AuditLogRepositoryInterface;
use App\Application\Contracts\Repositories\Inventory\InventoryItemRepositoryInterface;
use App\Application\Contracts\Repositories\Inventory\InventoryMovementRepositoryInterface;
use App\Application\Contracts\Repositories\Inventory\ProductCategoryRepositoryInterface;
use App\Application\Contracts\Repositories\Inventory\ProductRepositoryInterface;
use App\Application\Contracts\Repositories\Inventory\SupplierRepositoryInterface;
use App\Application\Contracts\Repositories\Inventory\WarehouseRepositoryInterface;
use App\Domain\Inventory\InventoryItem;
use App\Domain\Inventory\Product;
use App\Domain\Inventory\ProductCategory;
use App\Domain\Inventory\Supplier;
use App\Infrastructure\Persistence\Eloquent\Repositories\Audit\AuditLogRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Inventory\InventoryItemRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Inventory\InventoryMovementRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Inventory\ProductCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Inventory\ProductRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Inventory\SupplierRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\Inventory\WarehouseRepository;
use App\Policies\Inventory\InventoryItemPolicy;
use App\Policies\Inventory\ProductCategoryPolicy;
use App\Policies\Inventory\ProductPolicy;
use App\Policies\Inventory\SupplierPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductCategoryRepositoryInterface::class, ProductCategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(WarehouseRepositoryInterface::class, WarehouseRepository::class);
        $this->app->bind(InventoryItemRepositoryInterface::class, InventoryItemRepository::class);
        $this->app->bind(InventoryMovementRepositoryInterface::class, InventoryMovementRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(InventoryItem::class, InventoryItemPolicy::class);
    }
}

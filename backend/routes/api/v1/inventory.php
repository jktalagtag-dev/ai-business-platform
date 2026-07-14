<?php

use App\Http\Controllers\Api\V1\Inventory\CategoryController;
use App\Http\Controllers\Api\V1\Inventory\ProductController;
use App\Http\Controllers\Api\V1\Inventory\StockController;
use App\Http\Controllers\Api\V1\Inventory\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::apiResource('categories', CategoryController::class)->parameters(['categories' => 'category']);
    Route::apiResource('products', ProductController::class)->parameters(['products' => 'product']);
    Route::apiResource('suppliers', SupplierController::class)->parameters(['suppliers' => 'supplier']);

    Route::get('stock', [StockController::class, 'index']);
    Route::get('stock/{product}', [StockController::class, 'show']);
    Route::post('stock/{product}/adjust', [StockController::class, 'adjust']);
    Route::get('stock/{product}/movements', [StockController::class, 'movements']);
});

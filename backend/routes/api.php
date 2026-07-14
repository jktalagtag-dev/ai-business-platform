<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(base_path('routes/api/v1/auth.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/inventory.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/audit.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/employees.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/tickets.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/ai.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/knowledge-base.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/automation.php'));

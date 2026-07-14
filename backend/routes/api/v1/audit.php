<?php

use App\Http\Controllers\Api\V1\Audit\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant', 'role:owner,admin'])->get('audit-logs', [AuditLogController::class, 'index']);

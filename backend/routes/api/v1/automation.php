<?php

use App\Http\Controllers\Api\V1\Automation\AutomationJobController;
use App\Http\Controllers\Api\V1\Automation\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('automation')->group(function (): void {
    Route::apiResource('workflows', WorkflowController::class)
        ->parameters(['workflows' => 'workflow'])
        ->except(['update']);

    Route::get('workflows/{workflow}/steps', [WorkflowController::class, 'steps']);
    Route::post('workflows/{workflow}/activate', [WorkflowController::class, 'activate']);
    Route::post('workflows/{workflow}/pause', [WorkflowController::class, 'pause']);

    Route::get('jobs', [AutomationJobController::class, 'index']);
    Route::get('jobs/{job}', [AutomationJobController::class, 'show']);
    Route::get('jobs/{job}/steps', [AutomationJobController::class, 'steps']);
    Route::post('jobs/{job}/retry', [AutomationJobController::class, 'retry']);
});

<?php

use App\Http\Controllers\Api\V1\KnowledgeBase\AskController;
use App\Http\Controllers\Api\V1\KnowledgeBase\DocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('knowledge-base')->group(function (): void {
    Route::apiResource('documents', DocumentController::class)
        ->parameters(['documents' => 'document'])
        ->except(['update']);

    Route::post('ask', [AskController::class, 'store']);
});

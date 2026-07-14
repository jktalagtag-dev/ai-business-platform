<?php

use App\Http\Controllers\Api\V1\Ai\AiChatController;
use App\Http\Controllers\Api\V1\Ai\AiConversationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->prefix('ai')->group(function (): void {
    Route::apiResource('conversations', AiConversationController::class)
        ->parameters(['conversations' => 'conversation'])
        ->except(['update']);

    Route::get('conversations/{conversation}/messages', [AiConversationController::class, 'messages']);
    Route::post('conversations/{conversation}/messages', [AiChatController::class, 'store']);
});

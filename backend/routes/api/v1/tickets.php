<?php

use App\Http\Controllers\Api\V1\Ticket\TicketAttachmentController;
use App\Http\Controllers\Api\V1\Ticket\TicketCommentController;
use App\Http\Controllers\Api\V1\Ticket\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    // Registered before the {ticket} wildcard resource routes below so
    // "statistics" is never captured as a ticket id.
    Route::get('tickets/statistics', [TicketController::class, 'statistics']);

    Route::apiResource('tickets', TicketController::class)
        ->parameters(['tickets' => 'ticket'])
        ->except(['destroy']);

    Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign']);
    Route::patch('tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
    Route::post('tickets/{ticket}/close', [TicketController::class, 'close']);
    Route::post('tickets/{ticket}/reopen', [TicketController::class, 'reopen']);

    Route::get('tickets/{ticket}/comments', [TicketCommentController::class, 'index']);
    Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store']);

    Route::get('tickets/{ticket}/attachments', [TicketAttachmentController::class, 'index']);
    Route::post('tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store']);
});

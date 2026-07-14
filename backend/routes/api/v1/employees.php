<?php

use App\Http\Controllers\Api\V1\Employee\DepartmentController;
use App\Http\Controllers\Api\V1\Employee\EmployeeController;
use App\Http\Controllers\Api\V1\Employee\EmployeeNoteController;
use App\Http\Controllers\Api\V1\Employee\PositionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::apiResource('departments', DepartmentController::class)->parameters(['departments' => 'department']);
    Route::apiResource('positions', PositionController::class)->parameters(['positions' => 'position']);

    // Registered before the {employee} wildcard resource routes below so
    // "me" is never captured as an employee id.
    Route::get('employees/me', [EmployeeController::class, 'me']);

    Route::apiResource('employees', EmployeeController::class)->parameters(['employees' => 'employee']);
    Route::post('employees/{employee}/avatar', [EmployeeController::class, 'uploadAvatar']);
    Route::get('employees/{employee}/notes', [EmployeeNoteController::class, 'index']);
    Route::post('employees/{employee}/notes', [EmployeeNoteController::class, 'store']);
});

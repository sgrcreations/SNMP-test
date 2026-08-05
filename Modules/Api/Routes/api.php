<?php

use Illuminate\Support\Facades\Route;
use Modules\Api\Controllers\V1AuthController;
use Modules\Api\Controllers\V1DashboardController;
use Modules\Api\Controllers\V1DeviceController;

Route::post('/auth/login', [V1AuthController::class, 'login'])->name('api.v1.auth.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [V1AuthController::class, 'me'])->name('api.v1.auth.me');
    Route::post('/auth/logout', [V1AuthController::class, 'logout'])->name('api.v1.auth.logout');

    Route::get('/dashboard', V1DashboardController::class)->name('api.v1.dashboard');
    Route::apiResource('devices', V1DeviceController::class)->names('api.v1.devices');

    Route::get('/interfaces', fn () => response()->json(['message' => 'Interfaces API arrives in Phase 2.'], 501))->name('api.v1.interfaces');
    Route::get('/metrics', fn () => response()->json(['message' => 'Metrics API arrives in Phase 2.'], 501))->name('api.v1.metrics');
    Route::get('/alerts', fn () => response()->json(['message' => 'Alerts API arrives in Phase 2.'], 501))->name('api.v1.alerts');
});

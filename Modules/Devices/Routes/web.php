<?php

use Illuminate\Support\Facades\Route;
use Modules\Devices\Controllers\DeviceController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('devices', DeviceController::class);
});

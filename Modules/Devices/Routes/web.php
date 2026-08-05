<?php

use Illuminate\Support\Facades\Route;
use Modules\Devices\Controllers\DeviceController;
use Modules\Devices\Controllers\DeviceSnmpController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('devices', DeviceController::class);
    Route::post('devices/{device}/test-snmp', [DeviceSnmpController::class, 'test'])
        ->name('devices.test-snmp');
});

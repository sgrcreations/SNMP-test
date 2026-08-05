<?php

use Illuminate\Support\Facades\Route;
use Modules\Devices\Controllers\DeviceController;
use Modules\Devices\Controllers\DeviceMonitorController;
use Modules\Devices\Controllers\DeviceSnmpController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('devices', [DeviceMonitorController::class, 'index'])->name('devices.index');
    Route::get('map', [DeviceMonitorController::class, 'map'])->name('devices.map');
    Route::post('devices/sync-all', [DeviceMonitorController::class, 'syncAll'])->name('devices.sync-all');

    Route::get('devices/create', [DeviceController::class, 'create'])->name('devices.create');
    Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');

    Route::get('devices/{device}', [DeviceMonitorController::class, 'show'])->name('devices.show');
    Route::get('devices/{device}/edit', [DeviceController::class, 'edit'])->name('devices.edit');
    Route::put('devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
    Route::patch('devices/{device}', [DeviceController::class, 'update']);
    Route::delete('devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

    Route::post('devices/{device}/test-snmp', [DeviceSnmpController::class, 'test'])->name('devices.test-snmp');
    Route::post('devices/{device}/poll', [DeviceMonitorController::class, 'poll'])->name('devices.poll');
    Route::get('devices/{device}/metrics.json', [DeviceMonitorController::class, 'metrics'])->name('devices.metrics');
    Route::post('devices/{device}/interfaces/{interface}/toggle-uplink', [DeviceMonitorController::class, 'toggleUplink'])
        ->name('devices.interfaces.toggle-uplink');
});

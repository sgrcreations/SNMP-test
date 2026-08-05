<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\AgentUpdateController;
use Modules\Settings\Controllers\SettingController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/settings/agent', [AgentUpdateController::class, 'show'])->name('settings.agent');
    Route::post('/settings/agent/check', [AgentUpdateController::class, 'check'])->name('settings.agent.check');
    Route::post('/settings/agent/apply', [AgentUpdateController::class, 'apply'])->name('settings.agent.apply');
    Route::post('/settings/agent/sync-devices', [AgentUpdateController::class, 'syncDevices'])->name('settings.agent.sync-devices');
});

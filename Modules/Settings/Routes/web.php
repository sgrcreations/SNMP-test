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
    Route::post('/settings/agent/push-channel', [AgentUpdateController::class, 'pushChannel'])->name('settings.agent.push-channel');
});

// Public update channel consumed by on-prem agents (no auth).
Route::get('/updates/snmp-agent/{channel}/manifest.json', [\Modules\Settings\Controllers\AgentUpdateChannelController::class, 'manifest'])
    ->name('updates.agent.manifest');
Route::get('/updates/snmp-agent/{channel}/{filename}', [\Modules\Settings\Controllers\AgentUpdateChannelController::class, 'binary'])
    ->where('filename', 'snmpd-[0-9]+\\.[0-9]+\\.[0-9]+')
    ->name('updates.agent.binary');

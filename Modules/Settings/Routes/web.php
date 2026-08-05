<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Controllers\SettingController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Alerts\Controllers\AlertController;

Route::middleware(['web', 'auth', 'verified'])->group(function (): void {
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Interfaces\Controllers\InterfaceController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/interfaces', [InterfaceController::class, 'index'])->name('interfaces.index');
});

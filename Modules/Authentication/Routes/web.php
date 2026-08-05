<?php

use Illuminate\Support\Facades\Route;

// Reserved for future authentication module web routes (roles UI, audit log UI).
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function (): void {
    //
});

<?php

use Modules\Core\Http\Controllers\DashboardController;
use Modules\Core\Http\Controllers\GuestController;
use Modules\Core\Http\Controllers\ProfileController;
use Modules\Core\Http\Controllers\WelcomeController;

Route::get('/', [WelcomeController::class, 'index']);

// All authenticated routes with /tooldock prefix
Route::prefix('tooldock')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

Route::prefix('tooldock')->middleware('auth')->group(function () {
    Route::get('/welcome', [GuestController::class, 'index'])->name('guest.welcome');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

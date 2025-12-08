<?php

use Illuminate\Support\Facades\Route;
use Modules\Vault\Http\Controllers\VaultController;
use Modules\Vault\Http\Controllers\VaultDashboardController;
use Modules\Vault\Http\Controllers\VaultLockController;
use Modules\Vault\Http\Middleware\VaultLockMiddleware;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('vault/lock', [VaultLockController::class, 'show'])->name('vault.lock');
    Route::post('vault/unlock', [VaultLockController::class, 'unlock'])->name('vault.unlock');
    Route::post('vault/lock', [VaultLockController::class, 'lock'])->name('vault.lock.store');
    Route::post('vault/pin', [VaultLockController::class, 'setPin'])->name('vault.pin.set');
    Route::get('vault/lock/status', [VaultLockController::class, 'status'])->name('vault.lock.status');

    Route::middleware([VaultLockMiddleware::class])->group(function () {
        Route::resource('vault', VaultController::class)->except(['show'])->names([
            'index' => 'vault.index',
            'create' => 'vault.create',
            'store' => 'vault.store',
            'edit' => 'vault.edit',
            'update' => 'vault.update',
            'destroy' => 'vault.destroy',
        ]);

        Route::post('vault/generate-password', [VaultController::class, 'generatePassword'])->name('vault.generate-password');
        Route::post('vault/{vault}/toggle-favorite', [VaultController::class, 'toggleFavorite'])->name('vault.toggle-favorite');
        Route::get('vault/{vault}/generate-totp', [VaultController::class, 'generateTotp'])->name('vault.generate-totp');

        Route::get('vault/dashboard', [VaultDashboardController::class, 'index'])->name('vault.dashboard');
    });
});

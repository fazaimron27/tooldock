<?php

use Illuminate\Support\Facades\Route;
use Modules\Vault\Http\Controllers\VaultController;
use Modules\Vault\Http\Controllers\VaultDashboardController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
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

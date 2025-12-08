<?php

use Illuminate\Support\Facades\Route;
use Modules\Vault\Http\Controllers\VaultController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('vaults', VaultController::class)->names('vault');
});

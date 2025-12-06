<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Api\UserController;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(['web', 'auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cores', CoreController::class)->names('core');
    Route::get('users/search', [UserController::class, 'search'])->name('api.users.search');
});

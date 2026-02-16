<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Api\UserController;

Route::middleware(['web', 'auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('users/search', [UserController::class, 'search'])->name('users.search');
});

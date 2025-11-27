<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\RoleController;
use Modules\Core\Http\Controllers\UserController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('users', UserController::class)->names([
        'index' => 'core.users.index',
        'create' => 'core.users.create',
        'store' => 'core.users.store',
        'show' => 'core.users.show',
        'edit' => 'core.users.edit',
        'update' => 'core.users.update',
        'destroy' => 'core.users.destroy',
    ]);

    Route::resource('roles', RoleController::class)->names([
        'index' => 'core.roles.index',
        'create' => 'core.roles.create',
        'store' => 'core.roles.store',
        'show' => 'core.roles.show',
        'edit' => 'core.roles.edit',
        'update' => 'core.roles.update',
        'destroy' => 'core.roles.destroy',
    ]);
});

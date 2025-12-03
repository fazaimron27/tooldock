<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\DashboardController;
use Modules\Core\Http\Controllers\ModuleManagerController;
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

    Route::get('modules', [ModuleManagerController::class, 'index'])->name('core.modules.index');
    Route::post('modules/install', [ModuleManagerController::class, 'install'])->name('core.modules.install');
    Route::post('modules/uninstall', [ModuleManagerController::class, 'uninstall'])->name('core.modules.uninstall');
    Route::post('modules/toggle', [ModuleManagerController::class, 'toggle'])->name('core.modules.toggle');

    Route::get('core/dashboard', [DashboardController::class, 'module'])->name('core.dashboard');
});

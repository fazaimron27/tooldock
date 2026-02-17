<?php

/**
 * Routine Module Web Routes
 *
 * Defines web routes for the Routine habit tracker module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

use Illuminate\Support\Facades\Route;
use Modules\Routine\Http\Controllers\RoutineController;
use Modules\Routine\Http\Controllers\RoutineDashboardController;

Route::middleware(['web', 'auth', 'verified'])->prefix('tooldock')->group(function () {
    Route::resource('routine', RoutineController::class)
        ->except(['show', 'create', 'edit'])
        ->names([
            'index' => 'routine.index',
            'store' => 'routine.store',
            'update' => 'routine.update',
            'destroy' => 'routine.destroy',
        ]);

    Route::get('routine/dashboard', [RoutineDashboardController::class, 'index'])
        ->name('routine.dashboard');

    Route::post('routine/{habit}/toggle', [RoutineController::class, 'toggle'])
        ->name('routine.toggle');

    Route::get('routine/heatmap', [RoutineController::class, 'heatmapData'])
        ->name('routine.heatmap');
});

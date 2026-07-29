<?php

/**
 * QuickDraw Module Web Routes
 *
 * Defines web routes for whiteboard canvas CRUD and tldraw state sync.
 *
 * @author     Tool Dock Team
 * @license    MIT
 *
 * @see RouteServiceProvider::mapWebRoutes()
 */

use Illuminate\Support\Facades\Route;
use Modules\QuickDraw\Http\Controllers\QuickDrawController;
use Modules\QuickDraw\Http\Controllers\QuickDrawDashboardController;
use Modules\QuickDraw\Providers\RouteServiceProvider;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('quickdraw/dashboard', [QuickDrawDashboardController::class, 'index'])
        ->name('quickdraw.dashboard');

    Route::resource('quickdraw', QuickDrawController::class)
        ->only(['index', 'show', 'store', 'destroy'])
        ->names([
            'index' => 'quickdraw.index',
            'show' => 'quickdraw.show',
            'store' => 'quickdraw.store',
            'destroy' => 'quickdraw.destroy',
        ]);

    Route::post('quickdraw/{quickdraw}/sync', [QuickDrawController::class, 'sync'])
        ->name('quickdraw.sync');
});

<?php

/**
 * Folio Module Web Routes
 *
 * Defines web routes for resume CRUD, builder, and print page.
 *
 * @author     Tool Dock Team
 * @license    MIT
 *
 * @see \Modules\Folio\Providers\RouteServiceProvider::mapWebRoutes()
 */

use Illuminate\Support\Facades\Route;
use Modules\Folio\Http\Controllers\FolioController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('folio', FolioController::class)
        ->only(['index', 'store', 'edit', 'update', 'destroy'])
        ->names([
            'index' => 'folio.index',
            'store' => 'folio.store',
            'edit' => 'folio.edit',
            'update' => 'folio.update',
            'destroy' => 'folio.destroy',
        ]);

    Route::get('folio/{folio}/print', [FolioController::class, 'print'])
        ->name('folio.print');
});

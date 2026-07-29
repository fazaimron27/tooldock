<?php

/**
 * Nucleus Module Web Routes
 *
 * Defines web routes for the Nucleus JSON editor, including the main
 * editor view, snippet persistence, history retrieval, and deletion.
 *
 * @author     Tool Dock Team
 * @license    MIT
 *
 * @see RouteServiceProvider::mapWebRoutes()
 */

use Illuminate\Support\Facades\Route;
use Modules\Nucleus\Http\Controllers\NucleusController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('nucleus', [NucleusController::class, 'index'])->name('nucleus.index');
    Route::post('nucleus/snippets', [NucleusController::class, 'store'])->name('nucleus.snippets.store');
    Route::get('nucleus/history', [NucleusController::class, 'history'])->name('nucleus.history');
    Route::get('nucleus/snippets/{snippet}', [NucleusController::class, 'show'])->name('nucleus.snippets.show');
    Route::delete('nucleus/snippets/{snippet}', [NucleusController::class, 'destroy'])->name('nucleus.snippets.destroy');
});

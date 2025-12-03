<?php

use Illuminate\Support\Facades\Route;
use Modules\Categories\Http\Controllers\CategoriesController;
use Modules\Categories\Http\Controllers\CategoriesDashboardController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('categories', CategoriesController::class)->except(['show'])->names([
        'index' => 'categories.index',
        'create' => 'categories.create',
        'store' => 'categories.store',
        'edit' => 'categories.edit',
        'update' => 'categories.update',
        'destroy' => 'categories.destroy',
    ]);

    Route::get('categories/dashboard', [CategoriesDashboardController::class, 'index'])->name('categories.dashboard');
});

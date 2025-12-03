<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;
use Modules\Blog\Http\Controllers\BlogDashboardController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Register dashboard route BEFORE resource routes to avoid route conflict
    Route::get('blog/dashboard', [BlogDashboardController::class, 'index'])->name('blog.dashboard');

    Route::resource('blog', BlogController::class)->names([
        'index' => 'blog.index',
        'create' => 'blog.create',
        'store' => 'blog.store',
        'show' => 'blog.show',
        'edit' => 'blog.edit',
        'update' => 'blog.update',
        'destroy' => 'blog.destroy',
    ]);
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
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

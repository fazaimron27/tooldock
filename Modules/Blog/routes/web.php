<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Media index page for managing media files
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::delete('media/{medium}', [MediaController::class, 'destroy'])->name('media.destroy');
});

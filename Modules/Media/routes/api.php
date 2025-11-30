<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaController;

Route::middleware(['web', 'auth', 'throttle:media-uploads'])->prefix('v1')->group(function () {
    Route::post('media/upload-temporary', [MediaController::class, 'uploadTemporary'])->name('media.upload-temporary');
    Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');
});

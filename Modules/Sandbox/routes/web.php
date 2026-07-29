<?php

use Illuminate\Support\Facades\Route;
use Modules\Sandbox\Http\Controllers\SandboxController;

Route::middleware(['auth', 'verified', 'can:sandbox.intake.view'])->group(function () {
    Route::get('sandbox', [SandboxController::class, 'index'])->name('sandbox.index');
    Route::get('sandbox/entries', [SandboxController::class, 'entries'])->name('sandbox.entries');
});

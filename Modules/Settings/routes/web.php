<?php

use Illuminate\Support\Facades\Route;
use Modules\Settings\Http\Controllers\SettingsController;
use Modules\Settings\Http\Controllers\SettingsDashboardController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('settings/dashboard', [SettingsDashboardController::class, 'index'])->name('settings.dashboard');
});

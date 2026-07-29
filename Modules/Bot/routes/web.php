<?php

use Illuminate\Support\Facades\Route;
use Modules\Bot\Http\Controllers\BotConnectController;
use Modules\Bot\Http\Controllers\BotController;
use Modules\Bot\Http\Controllers\BotMessageController;
use Modules\Bot\Http\Controllers\BotPlatformController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('bot', [BotController::class, 'index'])->name('bot.index');
    Route::get('bot/dashboard', [BotController::class, 'dashboard'])->name('bot.dashboard');

    Route::post('bot/platform', [BotPlatformController::class, 'store'])->name('bot.platform.store');
    Route::put('bot/platform/{botPlatform}', [BotPlatformController::class, 'update'])->name('bot.platform.update');
    Route::delete('bot/platform/{botPlatform}', [BotPlatformController::class, 'destroy'])->name('bot.platform.destroy');
    Route::post('bot/platform/{botPlatform}/test', [BotPlatformController::class, 'test'])->name('bot.platform.test');

    Route::get('bot/messages', [BotMessageController::class, 'index'])->name('bot.messages.index');

    // Account linking (signed URL from /start bot command)
    Route::get('bot/connect', [BotConnectController::class, 'show'])->name('bot.connect');
    Route::post('bot/connect', [BotConnectController::class, 'store'])->name('bot.connect.store');
});

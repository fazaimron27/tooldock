<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bot_platform_id')->constrained('bot_platforms')->cascadeOnDelete();
            $table->string('platform_user_id');       // Discord user ID / Telegram user ID
            $table->string('platform_username');       // Display name shown on confirmation page
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // One platform user can only be connected to one Tool Dock account per platform
            $table->unique(['bot_platform_id', 'platform_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_connections');
    }
};

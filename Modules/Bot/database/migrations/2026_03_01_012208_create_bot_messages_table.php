<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('bot_platform_id')->constrained('bot_platforms')->cascadeOnDelete();
            $table->string('direction', 20)->default('outbound');
            $table->string('command_key', 255)->nullable()->index();
            $table->text('raw_payload')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'bot_platform_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_messages');
    }
};

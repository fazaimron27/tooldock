<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_platforms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('driver', 50)->index();
            $table->string('name', 255);
            $table->text('credentials')->nullable();
            $table->string('hook_inbound_slug')->nullable(); // Hook module inbound endpoint slug
            $table->boolean('is_active')->default(true);
            $table->timestamp('tested_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_platforms');
    }
};

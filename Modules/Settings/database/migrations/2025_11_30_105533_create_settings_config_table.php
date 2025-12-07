<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('module')->nullable()->index();
            $table->string('group')->index();
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            $table->enum('type', ['text', 'boolean', 'integer', 'textarea']);
            $table->string('label');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings_config');
    }
};

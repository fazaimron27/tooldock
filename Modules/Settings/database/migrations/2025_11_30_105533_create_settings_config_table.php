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
            $table->json('options')->nullable();
            $table->string('type'); // Validated by PHP SettingType enum
            $table->string('label');
            $table->boolean('is_system')->default(false);
            $table->string('scope', 10)->default('global')->index();
            $table->boolean('searchable')->default(false);
            $table->string('category')->nullable()->index();
            $table->string('category_label')->nullable();
            $table->string('category_description')->nullable();
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

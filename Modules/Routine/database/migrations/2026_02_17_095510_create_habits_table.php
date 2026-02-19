<?php

/**
 * Habits Table Migration
 *
 * Creates the habits table for tracking recurring habits.
 * Supports boolean (check/uncheck) and measurable (numeric value) types.
 * Uses UUIDs for primary and foreign keys.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('category_id')->nullable()->index();
            $table->string('name');
            $table->string('type', 20)->default('boolean');
            $table->string('icon')->default('target');
            $table->string('color', 7)->default('#10b981');
            $table->unsignedTinyInteger('goal_per_week')->default(7);
            $table->string('unit', 50)->nullable();
            $table->decimal('target_value', 10, 2)->nullable();
            $table->enum('status', ['active', 'paused', 'archived'])->default('active');
            $table->date('paused_at')->nullable();
            $table->date('resumed_at')->nullable();
            $table->unsignedInteger('streak_at_pause')->nullable();
            $table->timestamps();
        });

        Schema::table('habits', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};

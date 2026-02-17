<?php

/**
 * Habit Logs Table Migration
 *
 * Creates the habit_logs table for daily completion tracking.
 * Includes optional value column for measurable habit entries.
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
        Schema::create('habit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('habit_id')->index();
            $table->date('completed_at');
            $table->decimal('value', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['habit_id', 'completed_at']);
        });

        Schema::table('habit_logs', function (Blueprint $table) {
            $table->foreign('habit_id')
                ->references('id')
                ->on('habits')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('habit_logs');
    }
};

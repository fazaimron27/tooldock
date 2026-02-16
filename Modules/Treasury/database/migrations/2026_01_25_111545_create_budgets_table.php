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
        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('category_id')->index();
            $table->decimal('amount', 15, 2)->comment('Default monthly limit');
            $table->string('currency', 3)->default('IDR')->comment('ISO 4217 currency code for this budget');
            $table->boolean('is_active')->default(true)->comment('Is this template active?');
            $table->boolean('is_recurring')->default(true)->comment('Auto-create monthly instances');
            $table->boolean('rollover_enabled')->default(false)->comment('Carry unused to next month');
            $table->timestamps();

            $table->unique(['user_id', 'category_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'category_id', 'is_active'], 'idx_budgets_category_lookup');
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        Schema::create('budget_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('budget_id')->index();
            $table->string('period', 7)->comment('Format: YYYY-MM (e.g., 2026-01)');
            $table->decimal('amount', 15, 2)->comment('Budget limit for this period');
            $table->text('description')->nullable()->comment('Notes for this period');
            $table->timestamps();

            $table->unique(['budget_id', 'period']);
        });

        Schema::table('budget_periods', function (Blueprint $table) {
            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_periods');
        Schema::dropIfExists('budgets');
    }
};

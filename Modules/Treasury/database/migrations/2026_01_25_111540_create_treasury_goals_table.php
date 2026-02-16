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
        Schema::create('treasury_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('wallet_id')->nullable()->index();
            $table->uuid('category_id')->nullable()->index();
            $table->string('name', 150);
            $table->decimal('target_amount', 15, 2);
            $table->string('currency', 3)->default('IDR')->comment('ISO 4217 currency code for target and saved amounts');
            $table->date('deadline')->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_completed'], 'treasury_goals_user_completion_index');
        });

        Schema::table('treasury_goals', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_goals');
    }
};

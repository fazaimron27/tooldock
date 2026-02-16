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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('wallet_id')->index();
            $table->uuid('destination_wallet_id')->nullable()->index();
            $table->uuid('category_id')->nullable()->index();
            $table->uuid('goal_id')->nullable()->index();
            $table->enum('type', ['income', 'expense', 'transfer'])->index();
            $table->string('name', 100)->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('exchange_rate', 20, 10)->nullable()->comment('Exchange rate at time of transaction');
            $table->string('original_currency', 3)->nullable()->comment('Original currency if different from wallet');
            $table->string('description', 255)->nullable();
            $table->dateTimeTz('date')->index();
            $table->timestamps();

            $table->index(['user_id', 'wallet_id', 'date']);
            $table->index(['user_id', 'type', 'date']);

            $table->index(['user_id', 'category_id', 'type', 'date'], 'idx_transactions_budgeting');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            $table->foreign('destination_wallet_id')->references('id')->on('wallets')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('goal_id')->references('id')->on('treasury_goals')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

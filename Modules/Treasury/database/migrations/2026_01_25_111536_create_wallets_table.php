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
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('name', 100);
            $table->string('type', 50)->default('cash')->index();
            $table->decimal('balance', 15, 2)->default(0)->index();
            $table->string('currency', 3)->default('IDR');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Composite index for common wallet queries
            $table->index(['user_id', 'is_active']);

            // Composite index for user-specific balance queries and sorting
            $table->index(['user_id', 'balance']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};

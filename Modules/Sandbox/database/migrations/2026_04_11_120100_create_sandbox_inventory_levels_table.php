<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sandbox_inventory_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('warehouse_code', 32);
            $table->string('sku', 64);
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'warehouse_code', 'sku'], 'sandbox_inventory_unique');
            $table->index(['warehouse_code', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sandbox_inventory_levels');
    }
};

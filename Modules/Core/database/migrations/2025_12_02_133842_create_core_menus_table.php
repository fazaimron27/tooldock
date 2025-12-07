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
        Schema::create('core_menus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('group')->index();
            $table->string('label');
            $table->string('route')->index();
            $table->string('icon');
            $table->integer('order')->default(0);
            $table->string('permission')->nullable();
            $table->string('module')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['group', 'order']);
            $table->index('is_active');
            $table->index('parent_id');
        });

        Schema::table('core_menus', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('core_menus')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_menus');
    }
};

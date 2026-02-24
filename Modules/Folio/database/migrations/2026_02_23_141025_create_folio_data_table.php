<?php

/**
 * Create Folio Data Table Migration
 *
 * Creates the folio_data table for storing resume JSON content.
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
     */
    public function up(): void
    {
        Schema::create('folio_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('folio_id')->unique();
            $table->json('content');
            $table->timestamps();

            $table->foreign('folio_id')->references('id')->on('folios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folio_data');
    }
};

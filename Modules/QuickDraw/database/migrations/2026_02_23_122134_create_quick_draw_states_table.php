<?php

/**
 * Create Quick Draw States Table Migration
 *
 * Creates the quickdraw_states table for storing tldraw document snapshots.
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
        Schema::create('quickdraw_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quickdraw_id')->unique();
            $table->json('document_state');
            $table->timestamps();

            $table->foreign('quickdraw_id')->references('id')->on('quickdraws')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quickdraw_states');
    }
};

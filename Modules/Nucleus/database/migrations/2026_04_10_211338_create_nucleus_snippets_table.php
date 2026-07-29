<?php

/**
 * Create Nucleus Snippets Table Migration
 *
 * Creates the nucleus_snippets table for storing frequently used JSON
 * templates or data snapshots for quick access in the Nucleus editor.
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
        Schema::create('nucleus_snippets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('title')->index();
            $table->text('raw_json');
            $table->timestamps();
        });

        Schema::table('nucleus_snippets', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nucleus_snippets');
    }
};

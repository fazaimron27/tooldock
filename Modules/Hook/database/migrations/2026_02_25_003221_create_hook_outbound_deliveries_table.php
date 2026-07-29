<?php

/**
 * Create hook_outbound_deliveries table.
 *
 * Stores results of outbound webhook deliveries including
 * response status, headers, body, duration, and error details.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hook_outbound_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outbound_id')->constrained('hook_outbounds')->cascadeOnDelete();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('outbound_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hook_outbound_deliveries');
    }
};

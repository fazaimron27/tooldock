<?php

/**
 * Create hook_inbound_requests table.
 *
 * Stores individual inbound webhook requests received by inbound endpoints,
 * including method, headers, payload, query params, and source IP.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hook_inbound_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inbound_id')->constrained('hook_inbounds')->cascadeOnDelete();
            $table->string('method', 10);
            $table->text('url');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->json('query_params')->nullable();
            $table->string('source_ip', 45);
            $table->string('content_type', 255)->nullable();
            $table->timestamps();

            $table->index('inbound_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hook_inbound_requests');
    }
};

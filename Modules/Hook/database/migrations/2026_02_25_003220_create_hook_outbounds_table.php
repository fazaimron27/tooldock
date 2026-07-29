<?php

/**
 * Create hook_outbounds table.
 *
 * Stores outbound webhook configurations including target URL, HTTP method,
 * trigger key, provider type, provider credentials, header templates,
 * and payload templates.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hook_outbounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('target_url')->nullable();
            $table->string('method', 10)->default('POST');
            $table->string('trigger')->nullable()->index();
            $table->string('provider')->default('generic')->index();
            $table->text('provider_config')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload_template')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hook_outbounds');
    }
};

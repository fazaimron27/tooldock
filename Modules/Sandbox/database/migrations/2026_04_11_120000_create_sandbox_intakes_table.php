<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sandbox_intakes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('inbound_id')->constrained('hook_inbounds')->cascadeOnDelete();
            $table->foreignUuid('inbound_request_id')->constrained('hook_inbound_requests')->cascadeOnDelete();

            $table->string('event', 120);
            $table->uuid('correlation_id')->nullable();
            $table->dateTimeTz('occurred_at')->nullable();
            $table->string('warehouse_code', 32)->nullable();

            $table->string('status', 40)->index();
            $table->string('priority', 20)->nullable()->index();
            $table->string('routing_queue', 60)->nullable()->index();
            $table->boolean('requires_manual_review')->default(false)->index();

            $table->json('risk_flags')->nullable();
            $table->json('metrics')->nullable();
            $table->json('normalized_items')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('processing')->nullable();
            $table->json('payload')->nullable();

            $table->timestampTz('received_at')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('applied_at')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['inbound_id', 'created_at']);
            $table->unique(['inbound_id', 'correlation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sandbox_intakes');
    }
};

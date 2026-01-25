<?php

/**
 * Create Notifications Table Migration
 *
 * Creates the notifications table for storing user notifications.
 * This is Laravel's standard notifications table structure with
 * UUID primary keys and polymorphic notifiable relationship.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create the notifications table.
 *
 * Table structure:
 * - id: UUID primary key
 * - type: Notification class name
 * - notifiable_id/type: Polymorphic relationship to recipient
 * - data: JSON notification payload
 * - read_at: Timestamp when notification was read (nullable)
 * - created_at/updated_at: Standard timestamps
 *
 * Indexes:
 * - idx_notifications_unread: Optimizes unread notification queries
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the notifications table with appropriate columns
     * and indexes for efficient notification retrieval.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'idx_notifications_unread');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the notifications table.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

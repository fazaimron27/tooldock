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
        $tableNames = config('permission.table_names');

        Schema::create('group_user', function (Blueprint $table) use ($tableNames) {
            $table->foreignUuid('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained($tableNames['users'] ?? 'users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['group_id', 'user_id']);
            $table->index('user_id', 'idx_group_user_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_user');
    }
};

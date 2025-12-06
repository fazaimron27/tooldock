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

        Schema::create('group_has_roles', function (Blueprint $table) use ($tableNames) {
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('role_id')->constrained($tableNames['roles'])->onDelete('cascade');

            $table->timestamps();
            $table->primary(['group_id', 'role_id']);
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_has_roles');
    }
};

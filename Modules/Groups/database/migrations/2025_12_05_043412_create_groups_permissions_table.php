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
        $columnNames = config('permission.column_names');
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        Schema::create('groups_permissions', function (Blueprint $table) use ($tableNames, $pivotPermission) {
            $table->foreignUuid('group_id')->constrained('groups')->onDelete('cascade');
            $table->uuid($pivotPermission);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->timestamps();
            $table->unique(['group_id', $pivotPermission]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups_permissions');
    }
};

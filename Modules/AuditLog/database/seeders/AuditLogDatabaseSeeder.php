<?php

namespace Modules\AuditLog\Database\Seeders;

use Illuminate\Database\Seeder;

class AuditLogDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds permissions first. This seeder is reserved for future AuditLog module sample data.
     */
    public function run(): void
    {
        $this->call(AuditLogPermissionSeeder::class);
    }
}

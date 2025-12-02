<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;

class CoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds roles, permissions, and super admin user first.
     * This seeder is reserved for future Core module sample data or additional seeding needs.
     */
    public function run(): void
    {
        $this->call(CorePermissionSeeder::class);
    }
}

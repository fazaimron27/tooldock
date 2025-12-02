<?php

namespace Modules\Categories\Database\Seeders;

use App\Services\Registry\PermissionRegistry;
use Illuminate\Database\Seeder;

class CategoriesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Permissions are now registered in CategoriesServiceProvider.
     * This seeder ensures permissions are seeded if run manually.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->seed();
    }
}

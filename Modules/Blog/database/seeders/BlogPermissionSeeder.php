<?php

namespace Modules\Blog\Database\Seeders;

use App\Services\Registry\PermissionRegistry;
use Illuminate\Database\Seeder;

class BlogPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Permissions are now registered in BlogServiceProvider.
     * This seeder ensures permissions are seeded if run manually.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->seed();
    }
}

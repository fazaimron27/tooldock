<?php

namespace Modules\Newsletter\Database\Seeders;

use App\Services\Registry\PermissionRegistry;
use Illuminate\Database\Seeder;

class NewsletterPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Permissions are now registered in NewsletterServiceProvider.
     * This seeder ensures permissions are seeded if run manually.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->seed();
    }
}

<?php

namespace Modules\Media\Database\Seeders;

use App\Services\Registry\PermissionRegistry;
use Illuminate\Database\Seeder;

class MediaPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Permissions are now registered in MediaServiceProvider.
     * This seeder ensures permissions are seeded if run manually.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->seed();
    }
}

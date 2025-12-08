<?php

namespace Modules\Vault\Database\Seeders;

use App\Services\Registry\PermissionRegistry;
use Illuminate\Database\Seeder;

class VaultPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Permissions are now registered in VaultServiceProvider.
     * This seeder ensures permissions are seeded if run manually.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->seed();
    }
}

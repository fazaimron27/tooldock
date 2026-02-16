<?php

/**
 * Vault Permission Seeder
 *
 * Seeds vault module permissions via the central PermissionRegistry.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Database\Seeders;

use App\Services\Registry\PermissionRegistry;
use Illuminate\Database\Seeder;

/**
 * Class VaultPermissionSeeder
 *
 * Ensures vault permissions are seeded into the database when run manually.
 * Permissions are normally registered in VaultServiceProvider.
 *
 * @see \Modules\Vault\Providers\VaultServiceProvider
 */
class VaultPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Permissions are now registered in VaultServiceProvider.
     * This seeder ensures permissions are seeded if run manually.
     *
     * @return void
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->seed();
    }
}

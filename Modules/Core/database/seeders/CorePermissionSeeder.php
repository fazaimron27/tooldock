<?php

namespace Modules\Core\Database\Seeders;

use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\RoleRegistry;
use Illuminate\Database\Seeder;
use Modules\Core\Services\SuperAdminService;

class CorePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: Roles, permissions, and super admin user are now registered/created
     * in CoreServiceProvider during service provider boot. This seeder ensures
     * they are seeded if run manually (e.g., via artisan db:seed).
     */
    public function run(): void
    {
        app(RoleRegistry::class)->seed();
        app(PermissionRegistry::class)->seed();

        $roleRegistry = app(RoleRegistry::class);
        app(SuperAdminService::class)->ensureExists($roleRegistry, throwOnError: false);
    }
}

<?php

namespace Modules\Settings\Database\Seeders;

use App\Services\Registry\PermissionRegistry;
use Illuminate\Database\Seeder;

class SettingsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Permissions are now registered in SettingsServiceProvider during service provider boot.
     * This seeder ensures permissions are seeded when run manually (e.g., via artisan db:seed).
     * Called automatically by SettingsDatabaseSeeder to ensure correct seeding order.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->seed();
    }
}

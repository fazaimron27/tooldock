<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Services\PermissionRegistry;

class SettingsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->register('settings', [
            'config.view',
            'config.update',
        ], [
            'Administrator' => ['config.*'],
        ]);

        // Seed initial settings data (runs automatically during module installation)
        // Note: Auto-sync in ModuleLifecycleService also handles this, but this ensures
        // Settings module's default settings are seeded early in the installation process
        // before other modules are installed. This provides a safety net and explicit
        // seeding of Settings module's own default settings.
        $this->call(SettingsDatabaseSeeder::class);
    }
}

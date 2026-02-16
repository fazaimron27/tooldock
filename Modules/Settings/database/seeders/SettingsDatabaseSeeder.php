<?php

/**
 * Settings Database Seeder.
 *
 * Seeds permissions and all registered settings from the SettingsRegistry.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Settings\Database\Seeders;

use App\Services\Registry\SettingsRegistry;
use Illuminate\Database\Seeder;

class SettingsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds permissions first, then seeds all registered settings from the SettingsRegistry.
     * Settings are registered by modules in their service providers' boot methods.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call(SettingsPermissionSeeder::class);
        app(SettingsRegistry::class)->seed();
    }
}

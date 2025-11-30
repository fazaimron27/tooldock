<?php

namespace Modules\Settings\Database\Seeders;

use App\Services\SettingsRegistry;
use Illuminate\Database\Seeder;

class SettingsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds all registered settings from the SettingsRegistry.
     * Settings are registered by modules in their service providers' boot methods.
     */
    public function run(): void
    {
        app(SettingsRegistry::class)->seed();
    }
}

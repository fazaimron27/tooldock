<?php

namespace Modules\Categories\Database\Seeders;

use Illuminate\Database\Seeder;

class CategoriesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: CategoriesPermissionSeeder runs automatically during module installation
     * via ModulePermissionManager::runSeeder(). Categories are registered via
     * CategoryRegistry during service provider boot. This seeder is reserved for
     * future Categories module sample data or additional seeding needs.
     */
    public function run(): void {}
}

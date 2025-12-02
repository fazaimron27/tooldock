<?php

namespace Modules\Categories\Database\Seeders;

use Illuminate\Database\Seeder;

class CategoriesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds permissions first. Categories are registered via CategoryRegistry during service provider boot.
     * This seeder is reserved for future Categories module sample data or additional seeding needs.
     */
    public function run(): void
    {
        $this->call(CategoriesPermissionSeeder::class);
    }
}

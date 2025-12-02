<?php

namespace Modules\Media\Database\Seeders;

use Illuminate\Database\Seeder;

class MediaDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds permissions first. This seeder is reserved for future Media module sample data.
     */
    public function run(): void
    {
        $this->call(MediaPermissionSeeder::class);
    }
}

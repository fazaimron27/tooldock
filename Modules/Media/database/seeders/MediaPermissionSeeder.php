<?php

namespace Modules\Media\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Services\PermissionRegistry;

class MediaPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->register('media', [
            'files.view',
            'files.upload',
            'files.edit',
            'files.delete',
        ], [
            'Administrator' => ['files.*'],
            'Staff' => ['files.view', 'files.upload'],
        ]);
    }
}

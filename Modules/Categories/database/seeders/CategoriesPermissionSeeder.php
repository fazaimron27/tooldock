<?php

namespace Modules\Categories\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Constants\Roles as RoleConstants;
use Modules\Core\App\Services\PermissionRegistry;

class CategoriesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->register('categories', [
            'category.view',
            'category.create',
            'category.edit',
            'category.delete',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'category.*',
            ],
            RoleConstants::MANAGER => [
                'category.*',
            ],
        ]);
    }
}

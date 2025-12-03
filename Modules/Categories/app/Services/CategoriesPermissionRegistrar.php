<?php

namespace Modules\Categories\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\App\Constants\Roles as RoleConstants;

/**
 * Handles permission registration for the Categories module.
 */
class CategoriesPermissionRegistrar
{
    /**
     * Register default permissions for the Categories module.
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('categories', [
            'dashboard.view',
            'category.view',
            'category.create',
            'category.edit',
            'category.delete',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'dashboard.view',
                'category.*',
            ],
            RoleConstants::MANAGER => [
                'dashboard.view',
                'category.*',
            ],
        ]);
    }
}

<?php

/**
 * Categories Permission Registrar.
 *
 * Registers permissions and default role mappings for the Categories module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Categories\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Handles permission registration for the Categories module.
 */
class CategoriesPermissionRegistrar
{
    /**
     * Register default permissions for the Categories module.
     *
     * @param  PermissionRegistry  $registry  The permission registry
     * @return void
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

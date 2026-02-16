<?php

/**
 * Core Command Registrar.
 *
 * Registers artisan commands provided by the Core module
 * including user bulk creation commands.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Core module.
 *
 * Includes system-level commands like Profile, Settings, and Logout
 * that should always be available to authenticated users.
 */
class CoreCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Core module.
     *
     * @param  CommandRegistry  $registry  The command registry service
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'System', [
            [
                'label' => 'Profile',
                'route' => 'profile.edit',
                'icon' => 'user',
                'keywords' => ['account', 'profile', 'settings', 'personal'],
                'order' => 10,
            ],
            [
                'label' => 'Logout',
                'action' => 'logout',
                'icon' => 'log-out',
                'keywords' => ['sign out', 'exit', 'leave'],
                'order' => 100,
            ],
        ]);

        $registry->registerMany($moduleName, 'System', [
            [
                'label' => 'Users',
                'route' => 'core.users.index',
                'icon' => 'users',
                'parent' => 'User Management',
                'permission' => 'core.users.view',
                'keywords' => ['user', 'accounts', 'members'],
                'order' => 20,
            ],
            [
                'label' => 'Roles',
                'route' => 'core.roles.index',
                'icon' => 'shield',
                'parent' => 'User Management',
                'permission' => 'core.roles.manage',
                'keywords' => ['role', 'permissions', 'access'],
                'order' => 21,
            ],
        ]);

        $registry->registerMany($moduleName, 'Platform', [
            [
                'label' => 'Modules',
                'route' => 'core.modules.index',
                'icon' => 'package',
                'permission' => 'core.modules.manage',
                'keywords' => ['module', 'plugins', 'extensions', 'install'],
                'order' => 10,
            ],
        ]);
    }
}

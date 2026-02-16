<?php

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
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        // System commands (no parent - top level)
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

        // User Management group
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

        // Platform commands
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

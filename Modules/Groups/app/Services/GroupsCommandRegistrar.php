<?php

/**
 * Groups Command Registrar.
 *
 * Registers Command Palette commands for the Groups module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Groups\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Groups module.
 */
class GroupsCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Groups module.
     *
     * @param  CommandRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'System', [
            [
                'label' => 'Groups',
                'route' => 'groups.groups.index',
                'icon' => 'UserPlus',
                'parent' => 'User Management',
                'permission' => 'groups.group.view',
                'keywords' => ['group', 'collection', 'organize'],
                'order' => 40,
            ],
        ]);
    }
}

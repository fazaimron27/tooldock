<?php

/**
 * Bot Permission Registrar
 *
 * Registers granular permissions for the Bot module and maps them to roles.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

class BotPermissionRegistrar
{
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('bot', [
            'dashboard.view',
            'bridge.view',
            'bridge.create',
            'bridge.edit',
            'bridge.delete',
            'bridge.test',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'dashboard.view',
                'bridge.view',
                'bridge.create',
                'bridge.edit',
                'bridge.delete',
                'bridge.test',
            ],
            RoleConstants::MANAGER => [
                'dashboard.view',
                'bridge.view',
                'bridge.create',
                'bridge.edit',
                'bridge.delete',
                'bridge.test',
            ],
        ]);
    }
}

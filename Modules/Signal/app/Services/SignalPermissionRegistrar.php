<?php

/**
 * Signal Permission Registrar
 *
 * Registers default permissions for the Signal module with the
 * application's permission registry system.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Class SignalPermissionRegistrar
 *
 * Handles permission registration for the Signal module.
 * All roles receive full notification access by default.
 *
 * Permissions:
 * - notifications.signal.view: View notifications
 * - notifications.signal.manage: Mark as read and delete
 */
class SignalPermissionRegistrar
{
    /**
     * Register default permissions for the Signal module.
     *
     * Registers view and manage permissions under the 'notifications'
     * namespace. All default roles receive full access since users
     * should always be able to access their own notifications.
     *
     * @param  PermissionRegistry  $registry  The application permission registry
     * @return void
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('notifications', [
            'signal.view',
            'signal.manage',
        ], [
            RoleConstants::ADMINISTRATOR => ['signal.*'],
            RoleConstants::MANAGER => ['signal.*'],
            RoleConstants::STAFF => ['signal.*'],
            RoleConstants::AUDITOR => ['signal.*'],
            RoleConstants::GUEST => ['signal.*'],
        ]);
    }
}

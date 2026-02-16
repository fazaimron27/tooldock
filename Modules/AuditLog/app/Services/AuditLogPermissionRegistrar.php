<?php

/**
 * Audit Log Permission Registrar.
 *
 * Registers module-specific permissions and assigns them to
 * default roles (Administrator, Manager, Auditor).
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\AuditLog\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles;

/**
 * Handles permission registration for the AuditLog module.
 */
class AuditLogPermissionRegistrar
{
    /**
     * Register default permissions for the AuditLog module.
     *
     * @param  PermissionRegistry  $registry  The permission registry to register entries into
     * @return void
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('auditlog', [
            'view',
            'dashboard.view',
        ], [
            Roles::ADMINISTRATOR => ['view', 'dashboard.view'],
            Roles::MANAGER => ['view', 'dashboard.view'],
            Roles::AUDITOR => ['view', 'dashboard.view'],
        ]);
    }
}

<?php

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

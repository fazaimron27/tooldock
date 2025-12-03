<?php

namespace Modules\AuditLog\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the AuditLog module.
 */
class AuditLogMenuRegistrar
{
    /**
     * Register all menu items for the AuditLog module.
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'System',
            label: 'Audit Logs',
            route: 'auditlog.index',
            icon: 'FileText',
            order: 30,
            permission: 'auditlog.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Audit Log Dashboard',
            route: 'auditlog.dashboard',
            icon: 'LayoutDashboard',
            order: 60,
            permission: 'auditlog.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}

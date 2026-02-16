<?php

/**
 * Audit Log Menu Registrar.
 *
 * Registers sidebar menu items for the AuditLog module,
 * including links to the audit log index and dashboard views.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\AuditLog\Services;

use App\Services\Registry\MenuRegistry;

/**
 * Handles menu registration for the AuditLog module.
 */
class AuditLogMenuRegistrar
{
    /**
     * Register all menu items for the AuditLog module.
     *
     * @param  MenuRegistry  $menuRegistry  The menu registry to register items into
     * @param  string  $moduleName  The module name for ownership tracking
     * @return void
     */
    public function register(MenuRegistry $menuRegistry, string $moduleName): void
    {
        $menuRegistry->registerItem(
            group: 'System',
            label: 'Audit Logs',
            route: 'auditlog.index',
            icon: 'FileText',
            order: 60,
            permission: 'auditlog.view',
            parentKey: null,
            module: $moduleName
        );

        $menuRegistry->registerItem(
            group: 'Dashboard',
            label: 'Audit Log Dashboard',
            route: 'auditlog.dashboard',
            icon: 'LayoutDashboard',
            order: 50,
            permission: 'auditlog.dashboard.view',
            parentKey: 'dashboard',
            module: $moduleName
        );
    }
}

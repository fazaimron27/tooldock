<?php

/**
 * Audit Log Command Registrar.
 *
 * Registers Command Palette entries for the AuditLog module,
 * providing quick-access links to audit log views.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\AuditLog\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the AuditLog module.
 */
class AuditLogCommandRegistrar
{
    /**
     * Register all Command Palette commands for the AuditLog module.
     *
     * @param  CommandRegistry  $registry  The command registry to register entries into
     * @param  string  $moduleName  The module name for grouping commands
     * @return void
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'System', [
            [
                'label' => 'Audit Logs',
                'route' => 'auditlog.index',
                'icon' => 'file-text',
                'permission' => 'auditlog.view',
                'keywords' => ['audit', 'log', 'history', 'activity', 'trail'],
                'order' => 60,
            ],
        ]);
    }
}

<?php

namespace Modules\AuditLog\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the AuditLog module.
 */
class AuditLogCommandRegistrar
{
    /**
     * Register all Command Palette commands for the AuditLog module.
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

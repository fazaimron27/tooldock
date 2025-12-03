<?php

namespace Modules\Settings\Services;

use App\Services\Registry\PermissionRegistry;

/**
 * Handles permission registration for the Settings module.
 */
class SettingsPermissionRegistrar
{
    /**
     * Register default permissions for the Settings module.
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('settings', [
            'dashboard.view',
            'config.view',
            'config.update',
        ], [
            'Administrator' => ['dashboard.view', 'config.*'],
        ]);
    }
}

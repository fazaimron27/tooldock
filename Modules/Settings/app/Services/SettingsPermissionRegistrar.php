<?php

/**
 * Settings Permission Registrar.
 *
 * Registers permission definitions for the Settings module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Settings\Services;

use App\Services\Registry\PermissionRegistry;

/**
 * Handles permission registration for the Settings module.
 */
class SettingsPermissionRegistrar
{
    /**
     * Register default permissions for the Settings module.
     *
     * @param  PermissionRegistry  $registry
     * @return void
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

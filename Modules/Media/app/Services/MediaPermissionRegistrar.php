<?php

/**
 * Media Permission Registrar.
 *
 * Registers permission definitions for the Media module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Media\Services;

use App\Services\Registry\PermissionRegistry;

/**
 * Handles permission registration for the Media module.
 */
class MediaPermissionRegistrar
{
    /**
     * Register default permissions for the Media module.
     *
     * @param  PermissionRegistry  $registry
     * @return void
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('media', [
            'dashboard.view',
            'files.view',
            'files.upload',
            'files.edit',
            'files.delete',
        ], [
            'Administrator' => ['dashboard.view', 'files.*'],
            'Staff' => ['dashboard.view', 'files.view', 'files.upload'],
            'Guest' => ['files.upload'],
        ]);
    }
}

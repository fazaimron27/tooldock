<?php

namespace Modules\Newsletter\Services;

use App\Services\Registry\PermissionRegistry;

/**
 * Handles permission registration for the Newsletter module.
 */
class NewsletterPermissionRegistrar
{
    /**
     * Register default permissions for the Newsletter module.
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('newsletter', [
            'dashboard.view',
            'campaigns.view',
            'campaigns.create',
            'campaigns.edit',
            'campaigns.delete',
            'campaigns.send',
        ], [
            'Administrator' => ['dashboard.view', 'campaigns.*'],
            'Staff' => ['dashboard.view', 'campaigns.view', 'campaigns.create'],
        ]);
    }
}

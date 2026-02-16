<?php

namespace Modules\Treasury\Services;

use App\Services\Registry\PermissionRegistry;
use Modules\Core\Constants\Roles as RoleConstants;

/**
 * Handles permission registration for the Treasury module.
 */
class TreasuryPermissionRegistrar
{
    /**
     * Register default permissions for the Treasury module.
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('treasuries', [
            'dashboard.view',
            'treasury.view',
            'wallet.view',
            'wallet.create',
            'wallet.edit',
            'wallet.delete',
            'goal.view',
            'goal.create',
            'goal.edit',
            'goal.delete',
            'budget.view',
            'budget.create',
            'budget.edit',
            'budget.delete',
            'transaction.view',
            'transaction.create',
            'transaction.edit',
            'transaction.delete',
            'report.view',
            'preferences.view',
        ], [
            RoleConstants::ADMINISTRATOR => [
                'dashboard.view',
                'treasury.view',
                'wallet.*',
                'goal.*',
                'budget.*',
                'transaction.*',
                'report.view',
                'preferences.view',
            ],
            RoleConstants::MANAGER => [
                'dashboard.view',
                'treasury.view',
                'wallet.*',
                'goal.*',
                'budget.*',
                'transaction.*',
                'report.view',
                'preferences.view',
            ],
        ]);
    }
}

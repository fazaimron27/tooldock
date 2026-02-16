<?php

/**
 * Vault Dashboard Service
 *
 * Registers dashboard widgets for the Vault module overview.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Vault\Models\Vault;

/**
 * Class VaultDashboardService
 *
 * Provides statistical dashboard widgets for vault item counts
 * broken down by type (login, card, note, server) and favorites.
 *
 * @see \App\Services\Registry\DashboardWidgetRegistry
 */
class VaultDashboardService
{
    /**
     * Register all dashboard widgets for the Vault module.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry  The central widget registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'Secure Vault',
            'Securely store and manage sensitive credentials and private data.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Vault Items',
                value: fn () => Vault::forUser()->count(),
                icon: 'ShieldCheck',
                module: $moduleName,
                order: 40,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Favorite Items',
                value: fn () => Vault::forUser()->where('is_favorite', true)->count(),
                icon: 'Star',
                module: $moduleName,
                order: 41,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Login Credentials',
                value: fn () => Vault::forUser()->where('type', 'login')->count(),
                icon: 'Key',
                module: $moduleName,
                order: 42,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Credit Cards',
                value: fn () => Vault::forUser()->where('type', 'card')->count(),
                icon: 'Briefcase',
                module: $moduleName,
                order: 43,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Secure Notes',
                value: fn () => Vault::forUser()->where('type', 'note')->count(),
                icon: 'FileText',
                module: $moduleName,
                order: 44,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Server Credentials',
                value: fn () => Vault::forUser()->where('type', 'server')->count(),
                icon: 'HardDrive',
                module: $moduleName,
                order: 45,
                scope: 'detail'
            )
        );
    }
}

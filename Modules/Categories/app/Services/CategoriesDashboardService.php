<?php

namespace Modules\Categories\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Categories\Models\Category;

/**
 * Handles dashboard widget registration for the Categories module.
 */
class CategoriesDashboardService
{
    /**
     * Register all dashboard widgets for the Categories module.
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'Category Management',
            'Manage transaction categories and classifications.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Categories',
                value: fn () => Category::count(),
                icon: 'Tag',
                module: $moduleName,
                order: 30,
                scope: 'overview'
            )
        );
    }
}

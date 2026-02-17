<?php

/**
 * Routine Dashboard Service
 *
 * Registers dashboard widgets for the Routine module overview.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Routine\Models\Habit;

/**
 * Class RoutineDashboardService
 *
 * Provides statistical dashboard widgets for habit tracking metrics.
 *
 * @see \App\Services\Registry\DashboardWidgetRegistry
 */
class RoutineDashboardService
{
    /**
     * Register all dashboard widgets for the Routine module.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry  The central widget registry
     * @param  string  $moduleName  The module name identifier
     * @return void
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'Routine Tracker',
            'Track daily habits and build consistency streaks.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Active Habits',
                value: fn () => Habit::forUser()->active()->count(),
                icon: 'Repeat',
                module: $moduleName,
                order: 50,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Best Streak',
                value: fn () => Habit::forUser()->active()->get()->max('current_streak') ?? 0,
                icon: 'Flame',
                module: $moduleName,
                order: 51,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Archived Habits',
                value: fn () => Habit::forUser()->archived()->count(),
                icon: 'Archive',
                module: $moduleName,
                order: 52,
                scope: 'detail'
            )
        );
    }
}

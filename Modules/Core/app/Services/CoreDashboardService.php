<?php

namespace Modules\Core\App\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Core\App\Models\Permission;
use Modules\Core\App\Models\Role;
use Modules\Core\App\Models\User;

/**
 * Handles dashboard widget registration and data retrieval for the Core module.
 */
class CoreDashboardService
{
    /**
     * Register all dashboard widgets for the Core module.
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Users',
                value: fn () => User::count(),
                icon: 'Users',
                module: $moduleName,
                group: 'User Management',
                order: 10,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Roles',
                value: fn () => Role::count(),
                icon: 'Shield',
                module: $moduleName,
                group: 'User Management',
                order: 11,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Permissions',
                value: fn () => Permission::count(),
                icon: 'Key',
                module: $moduleName,
                group: 'User Management',
                order: 12,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'chart',
                title: 'User Growth',
                value: 0,
                icon: 'TrendingUp',
                module: $moduleName,
                group: 'Analytics',
                description: 'New user registrations over the last 6 months',
                chartType: 'line',
                data: fn () => $this->getUserGrowthData(),
                order: 13,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Users',
                value: 0,
                icon: 'UserPlus',
                module: $moduleName,
                group: 'Activity',
                description: 'Latest user registrations',
                data: fn () => $this->getRecentUsersActivity(),
                order: 14,
                scope: 'detail'
            )
        );
    }

    /**
     * Get user growth data for chart widget.
     *
     * Uses a single query with GROUP BY instead of multiple separate queries.
     */
    private function getUserGrowthData(): array
    {
        $now = now();
        $startDate = $now->copy()->subMonths(5)->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        $results = User::selectRaw('
                DATE_TRUNC(\'month\', created_at)::date as month,
                COUNT(*) as count
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy(function ($item) {
                return substr($item->month, 0, 7);
            })
            ->map(fn ($item) => (int) $item->count)
            ->toArray();

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthKey = $month->format('Y-m');

            $months[] = [
                'name' => $month->format('M Y'),
                'value' => $results[$monthKey] ?? 0,
            ];
        }

        return $months;
    }

    /**
     * Get recent users activity for activity widget.
     */
    private function getRecentUsersActivity(): array
    {
        return User::latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'title' => "New user registered: {$user->name}",
                    'timestamp' => $user->created_at->diffForHumans(),
                    'icon' => 'UserPlus',
                    'iconColor' => 'bg-blue-500',
                ];
            })
            ->toArray();
    }
}

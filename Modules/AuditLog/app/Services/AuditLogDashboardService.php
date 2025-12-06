<?php

namespace Modules\AuditLog\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\AuditLog\App\Models\AuditLog;

/**
 * Handles dashboard widget registration and data retrieval for the AuditLog module.
 */
class AuditLogDashboardService
{
    /**
     * Register all dashboard widgets for the AuditLog module.
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Audit Logs',
                value: fn () => AuditLog::count(),
                icon: 'FileText',
                module: $moduleName,
                order: 60,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'chart',
                title: 'Audit Events',
                value: 0,
                icon: 'Activity',
                module: $moduleName,
                description: 'Audit events over the last 7 days',
                chartType: 'area',
                data: fn () => $this->getAuditEventsData(),
                config: [
                    'created' => [
                        'label' => 'Created',
                        'color' => 'hsl(var(--chart-1))',
                    ],
                    'updated' => [
                        'label' => 'Updated',
                        'color' => 'hsl(var(--chart-2))',
                    ],
                    'deleted' => [
                        'label' => 'Deleted',
                        'color' => 'hsl(var(--chart-3))',
                    ],
                    'registered' => [
                        'label' => 'Registered',
                        'color' => 'hsl(var(--chart-4))',
                    ],
                    'login' => [
                        'label' => 'Login',
                        'color' => 'hsl(var(--chart-5))',
                    ],
                    'logout' => [
                        'label' => 'Logout',
                        'color' => 'hsl(var(--chart-6))',
                    ],
                ],
                xAxisKey: 'date',
                dataKeys: ['created', 'updated', 'deleted', 'registered', 'login', 'logout'],
                order: 61,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Audit Logs',
                value: 0,
                icon: 'Shield',
                module: $moduleName,
                description: 'Latest system activities',
                data: fn () => $this->getRecentAuditLogsActivity(),
                order: 62,
                scope: 'detail'
            )
        );
    }

    /**
     * Get audit events data for chart widget.
     *
     * Uses a single query with GROUP BY instead of multiple separate queries.
     */
    private function getAuditEventsData(): array
    {
        $now = now();
        $startDate = $now->copy()->subDays(6)->startOfDay();
        $endDate = $now->copy()->endOfDay();

        $results = AuditLog::selectRaw('
                DATE(created_at) as date,
                event,
                COUNT(*) as count
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('event', ['created', 'updated', 'deleted', 'registered', 'login', 'logout'])
            ->groupBy('date', 'event')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($dayLogs) {
                return $dayLogs->pluck('count', 'event')->toArray();
            })
            ->toArray();

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $dateKey = $day->format('Y-m-d');

            $dayData = $results[$dateKey] ?? [];

            $days[] = [
                'date' => $day->format('M d'),
                'created' => $dayData['created'] ?? 0,
                'updated' => $dayData['updated'] ?? 0,
                'deleted' => $dayData['deleted'] ?? 0,
                'registered' => $dayData['registered'] ?? 0,
                'login' => $dayData['login'] ?? 0,
                'logout' => $dayData['logout'] ?? 0,
            ];
        }

        return $days;
    }

    /**
     * Get recent audit logs activity for activity widget.
     */
    private function getRecentAuditLogsActivity(): array
    {
        return AuditLog::latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($log) {
                $eventIcon = match ($log->event) {
                    'created', 'registered' => 'Plus',
                    'updated' => 'Edit',
                    'deleted' => 'Trash',
                    'login' => 'LogIn',
                    'logout' => 'LogOut',
                    default => 'Activity',
                };

                $eventColor = match ($log->event) {
                    'created', 'registered' => 'bg-green-500',
                    'updated' => 'bg-blue-500',
                    'deleted' => 'bg-red-500',
                    'login' => 'bg-indigo-500',
                    'logout' => 'bg-amber-500',
                    default => 'bg-gray-500',
                };

                $modelName = class_basename($log->auditable_type ?? 'Unknown');

                return [
                    'id' => $log->id,
                    'title' => ucfirst($log->event)." {$modelName}",
                    'timestamp' => $log->created_at->diffForHumans(),
                    'icon' => $eventIcon,
                    'iconColor' => $eventColor,
                ];
            })
            ->toArray();
    }
}

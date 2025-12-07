<?php

namespace Modules\AuditLog\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Models\AuditLog;

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
                config: $this->getChartConfig(),
                xAxisKey: 'date',
                dataKeys: AuditLogEvent::allEvents(),
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
     * Get chart configuration for all possible audit events.
     *
     * @return array<string, array{label: string, color: string}>
     */
    private function getChartConfig(): array
    {
        $chartColors = [
            'hsl(var(--chart-1))',
            'hsl(var(--chart-2))',
            'hsl(var(--chart-3))',
            'hsl(var(--chart-4))',
            'hsl(var(--chart-5))',
            'hsl(var(--chart-6))',
            'hsl(var(--chart-7))',
            'hsl(var(--chart-8))',
            'hsl(var(--chart-9))',
            'hsl(var(--chart-10))',
            'hsl(var(--chart-11))',
            'hsl(var(--chart-12))',
        ];

        $allEvents = AuditLogEvent::allEvents();
        $config = [];

        foreach ($allEvents as $index => $event) {
            $config[$event] = [
                'label' => ucfirst(str_replace('_', ' ', $event)),
                'color' => $chartColors[$index % count($chartColors)],
            ];
        }

        return $config;
    }

    /**
     * Get audit events data for chart widget.
     *
     * Uses a single query with GROUP BY instead of multiple separate queries.
     * Includes all events (with 0 for events that didn't occur).
     */
    private function getAuditEventsData(): array
    {
        $now = now();
        $startDate = $now->copy()->subDays(6)->startOfDay();
        $endDate = $now->copy()->endOfDay();
        $allEvents = AuditLogEvent::allEvents();

        $results = AuditLog::selectRaw('
                DATE(created_at) as date,
                event,
                COUNT(*) as count
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('event', $allEvents)
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

            $dayRow = ['date' => $day->format('M d')];
            foreach ($allEvents as $event) {
                $dayRow[$event] = $dayData[$event] ?? 0;
            }
            $days[] = $dayRow;
        }

        return $days;
    }

    /**
     * Get recent audit logs activity for activity widget.
     */
    private function getRecentAuditLogsActivity(): array
    {
        return AuditLog::with('user:id,name')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($log) {
                $modelName = class_basename($log->auditable_type ?? 'Unknown');

                return [
                    'id' => $log->id,
                    'title' => ucfirst(str_replace('_', ' ', $log->event))." {$modelName}",
                    'timestamp' => $log->created_at->diffForHumans(),
                    'icon' => AuditLogEvent::getIcon($log->event),
                    'iconColor' => AuditLogEvent::getColor($log->event),
                ];
            })
            ->toArray();
    }
}

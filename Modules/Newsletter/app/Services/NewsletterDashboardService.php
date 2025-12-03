<?php

namespace Modules\Newsletter\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Newsletter\Models\Campaign;

/**
 * Handles dashboard widget registration and data retrieval for the Newsletter module.
 */
class NewsletterDashboardService
{
    /**
     * Register all dashboard widgets for the Newsletter module.
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Campaigns',
                value: fn () => Campaign::count(),
                icon: 'Send',
                module: $moduleName,
                order: 50,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'chart',
                title: 'Campaign Status',
                value: 0,
                icon: 'PieChart',
                module: $moduleName,
                description: 'Distribution of campaign statuses',
                chartType: 'bar',
                data: fn () => $this->getCampaignStatusData(),
                order: 51,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Campaigns',
                value: 0,
                icon: 'Send',
                module: $moduleName,
                description: 'Latest newsletter campaigns',
                data: fn () => $this->getRecentCampaignsActivity(),
                order: 52,
                scope: 'detail'
            )
        );
    }

    /**
     * Get campaign status data for chart widget.
     *
     * Uses a single query with GROUP BY instead of multiple queries.
     */
    private function getCampaignStatusData(): array
    {
        $statuses = ['draft', 'sending', 'sent'];

        $results = Campaign::selectRaw('status, COUNT(*) as count')
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $data = [];
        foreach ($statuses as $status) {
            $data[] = [
                'name' => ucfirst($status),
                'value' => $results[$status] ?? 0,
            ];
        }

        return $data;
    }

    /**
     * Get recent campaigns activity for activity widget.
     */
    private function getRecentCampaignsActivity(): array
    {
        return Campaign::latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($campaign) {
                $statusIcon = match ($campaign->status) {
                    'sent' => 'CheckCircle',
                    'sending' => 'Send',
                    'draft' => 'Edit',
                    default => 'Send',
                };

                $statusColor = match ($campaign->status) {
                    'sent' => 'bg-green-500',
                    'sending' => 'bg-blue-500',
                    'draft' => 'bg-gray-500',
                    default => 'bg-gray-500',
                };

                return [
                    'id' => $campaign->id,
                    'title' => "Campaign: {$campaign->subject} ({$campaign->status})",
                    'timestamp' => $campaign->created_at->diffForHumans(),
                    'icon' => $statusIcon,
                    'iconColor' => $statusColor,
                ];
            })
            ->toArray();
    }
}

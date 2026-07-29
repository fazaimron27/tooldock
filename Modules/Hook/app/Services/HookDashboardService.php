<?php

/**
 * Hook Dashboard Service.
 *
 * Handles dashboard widget registration and data retrieval
 * for the Hook module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Hook\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Hook\Models\HookInbound;
use Modules\Hook\Models\HookOutbound;
use Modules\Hook\Models\HookOutboundDelivery;

/**
 * Handles dashboard widget registration and data retrieval for the Hook module.
 */
class HookDashboardService
{
    /**
     * Register all dashboard widgets for the Hook module.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'Hook',
            'Manage inbound and outbound webhook events to connect external services.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Inbound Webhooks',
                value: fn () => HookInbound::forUser(Auth::user())->count(),
                icon: 'Webhook',
                module: $moduleName,
                order: 50,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Outbound Webhooks',
                value: fn () => HookOutbound::forUser(Auth::user())->count(),
                icon: 'Send',
                module: $moduleName,
                order: 51,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Webhook Deliveries',
                value: 0,
                icon: 'Activity',
                module: $moduleName,
                description: 'Latest outbound webhook delivery attempts',
                data: fn () => $this->getRecentDeliveries(),
                order: 52,
                scope: 'detail'
            )
        );
    }

    /**
     * Get recent outbound webhook deliveries for the activity widget.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecentDeliveries(): array
    {
        $user = Auth::user();

        return HookOutboundDelivery::with('outbound')
            ->whereHas('outbound', fn (Builder $query) => $query->forUser($user))
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($delivery) {
                $success = $delivery->response_status !== null
                    && $delivery->response_status >= 200
                    && $delivery->response_status < 300;

                return [
                    'id' => $delivery->id,
                    'title' => ($delivery->outbound?->name ?? 'Webhook').': '.($success ? 'Delivered' : 'Failed'),
                    'timestamp' => $delivery->created_at->diffForHumans(),
                    'icon' => $success ? 'CheckCircle' : 'XCircle',
                    'iconColor' => $success ? 'bg-green-500' : 'bg-red-500',
                ];
            })
            ->toArray();
    }
}

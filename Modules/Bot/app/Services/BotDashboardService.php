<?php

/**
 * Bot Dashboard Service.
 *
 * Handles dashboard widget registration and data retrieval
 * for the Bot module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Bot\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Auth;
use Modules\Bot\Enums\BotMessageDirection;
use Modules\Bot\Enums\BotMessageStatus;
use Modules\Bot\Models\BotMessage;
use Modules\Bot\Models\BotPlatform;

/**
 * Handles dashboard widget registration and data retrieval for the Bot module.
 */
class BotDashboardService
{
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'Bot',
            'Manage multi-platform bot integrations for Telegram and Discord.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Bot Integrations',
                value: fn () => BotPlatform::forUser(Auth::user())->count(),
                icon: 'Bot',
                module: $moduleName,
                order: 80,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Messages Sent',
                value: fn () => BotMessage::whereIn(
                    'bot_platform_id',
                    BotPlatform::forUser(Auth::user())->select('id')
                )->where('direction', BotMessageDirection::Outbound)->count(),
                icon: 'MessageCircle',
                module: $moduleName,
                order: 81,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Bot Messages',
                value: 0,
                icon: 'Activity',
                module: $moduleName,
                description: 'Latest bot message deliveries',
                data: fn () => $this->getRecentMessages(),
                order: 82,
                scope: 'detail'
            )
        );
    }

    private function getRecentMessages(): array
    {
        return BotMessage::with('platform')
            ->whereIn('bot_platform_id', BotPlatform::forUser(Auth::user())->select('id'))
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($message) {
                $success = $message->status === BotMessageStatus::Delivered;

                return [
                    'id' => $message->id,
                    'title' => ($message->platform?->name ?? 'Bot').': '.($success ? 'Delivered' : 'Failed'),
                    'timestamp' => $message->created_at->diffForHumans(),
                    'icon' => $success ? 'CheckCircle' : 'XCircle',
                    'iconColor' => $success ? 'bg-green-500' : 'bg-red-500',
                ];
            })
            ->toArray();
    }
}

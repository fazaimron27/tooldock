<?php

namespace Modules\Groups\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Groups\Models\Group;

/**
 * Handles dashboard widget registration and data retrieval for the Groups module.
 */
class GroupsDashboardService
{
    /**
     * Register all dashboard widgets for the Groups module.
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Groups',
                value: fn () => Group::count(),
                icon: 'Users',
                module: $moduleName,
                group: 'Groups',
                order: 20,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Group Members',
                value: fn () => User::has('groups')->distinct()->count('id'),
                icon: 'UserCheck',
                module: $moduleName,
                group: 'Groups',
                order: 21,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Average Members per Group',
                value: fn () => $this->getAverageMembersPerGroup(),
                icon: 'Users',
                module: $moduleName,
                group: 'Groups',
                order: 22,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'table',
                title: 'Largest Groups',
                value: 0,
                icon: 'TrendingUp',
                module: $moduleName,
                group: 'Groups',
                description: 'Groups with the most members',
                data: fn () => $this->getLargestGroups(),
                order: 23,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Groups with Permissions',
                value: fn () => Group::has('permissions')->distinct()->count('id'),
                icon: 'Key',
                module: $moduleName,
                group: 'Groups',
                order: 24,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Groups with Roles',
                value: fn () => Group::has('roles')->distinct()->count('id'),
                icon: 'Shield',
                module: $moduleName,
                group: 'Groups',
                order: 25,
                scope: 'detail'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Group Changes',
                value: 0,
                icon: 'Activity',
                module: $moduleName,
                group: 'Activity',
                description: 'Latest group activities',
                data: fn () => $this->getRecentGroupActivity(),
                order: 26,
                scope: 'detail'
            )
        );
    }

    /**
     * Get average members per group.
     */
    private function getAverageMembersPerGroup(): string
    {
        $totalGroups = Group::count();

        if ($totalGroups === 0) {
            return '0';
        }

        $totalMemberships = (int) DB::table('groups_users')->count();

        $average = round($totalMemberships / $totalGroups, 1);

        return (string) $average;
    }

    /**
     * Get largest groups by member count.
     */
    private function getLargestGroups(): array
    {
        return Group::withCount('users')
            ->orderBy('users_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'members' => $group->users_count,
                    'description' => $group->description,
                ];
            })
            ->toArray();
    }

    /**
     * Get recent group activity for activity widget.
     */
    private function getRecentGroupActivity(): array
    {
        return Group::withCount('users')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'title' => "Group updated: {$group->name} ({$group->users_count} members)",
                    'timestamp' => $group->updated_at->diffForHumans(),
                    'icon' => 'Users',
                    'iconColor' => 'bg-indigo-500',
                ];
            })
            ->toArray();
    }
}

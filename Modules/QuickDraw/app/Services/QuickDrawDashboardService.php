<?php

/**
 * QuickDraw Dashboard Service.
 *
 * Handles dashboard widget registration and data retrieval
 * for the QuickDraw module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\QuickDraw\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Auth;
use Modules\QuickDraw\Models\QuickDraw;

/**
 * Handles dashboard widget registration and data retrieval for the QuickDraw module.
 */
class QuickDrawDashboardService
{
    /**
     * Register all dashboard widgets for the QuickDraw module.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'QuickDraw',
            'Collaborative canvas for sketches, diagrams, and visual notes.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Drawings',
                value: fn () => QuickDraw::forUser(Auth::user())->count(),
                icon: 'PenTool',
                module: $moduleName,
                order: 60,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Drawings',
                value: 0,
                icon: 'PenTool',
                module: $moduleName,
                description: 'Recently created or updated drawings',
                data: fn () => $this->getRecentDrawings(),
                order: 61,
                scope: 'detail'
            )
        );
    }

    /**
     * Get recently updated drawings for the activity widget.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecentDrawings(): array
    {
        return QuickDraw::forUser(Auth::user())
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($drawing) {
                return [
                    'id' => $drawing->id,
                    'title' => $drawing->name ?? 'Untitled Drawing',
                    'timestamp' => $drawing->updated_at->diffForHumans(),
                    'icon' => 'PenTool',
                    'iconColor' => 'bg-violet-500',
                ];
            })
            ->toArray();
    }
}

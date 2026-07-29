<?php

/**
 * Folio Dashboard Service.
 *
 * Handles dashboard widget registration and data retrieval
 * for the Folio module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Folio\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Auth;
use Modules\Folio\Models\Folio;

/**
 * Handles dashboard widget registration and data retrieval for the Folio module.
 */
class FolioDashboardService
{
    /**
     * Register all dashboard widgets for the Folio module.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'Folio',
            'Create and manage professional resumes and portfolios.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Folios',
                value: fn () => Folio::forUser(Auth::user())->count(),
                icon: 'FileUser',
                module: $moduleName,
                order: 55,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Folios',
                value: 0,
                icon: 'FileUser',
                module: $moduleName,
                description: 'Recently created or updated resumes',
                data: fn () => $this->getRecentFolios(),
                order: 56,
                scope: 'detail'
            )
        );
    }

    /**
     * Get recently updated folios for the activity widget.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecentFolios(): array
    {
        return Folio::forUser(Auth::user())
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($folio) {
                return [
                    'id' => $folio->id,
                    'title' => $folio->name ?? 'Untitled Folio',
                    'timestamp' => $folio->updated_at->diffForHumans(),
                    'icon' => 'FileUser',
                    'iconColor' => 'bg-blue-500',
                ];
            })
            ->toArray();
    }
}

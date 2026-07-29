<?php

/**
 * Nucleus Dashboard Service.
 *
 * Handles dashboard widget registration and data retrieval
 * for the Nucleus module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Services;

use App\Data\DashboardWidget;
use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Auth;
use Modules\Nucleus\Models\NucleusSnippet;

/**
 * Handles dashboard widget registration and data retrieval for the Nucleus module.
 */
class NucleusDashboardService
{
    /**
     * Register all dashboard widgets for the Nucleus module.
     *
     * @param  DashboardWidgetRegistry  $widgetRegistry
     * @param  string  $moduleName
     * @return void
     */
    public function registerWidgets(DashboardWidgetRegistry $widgetRegistry, string $moduleName): void
    {
        $widgetRegistry->registerModuleMetadata(
            $moduleName,
            'Nucleus',
            'Advanced JSON core editor, viewer, and data parser.'
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'stat',
                title: 'Total Snippets',
                value: fn () => NucleusSnippet::forUser(Auth::user())->count(),
                icon: 'Braces',
                module: $moduleName,
                order: 70,
                scope: 'overview'
            )
        );

        $widgetRegistry->register(
            new DashboardWidget(
                type: 'activity',
                title: 'Recent Snippets',
                value: 0,
                icon: 'Braces',
                module: $moduleName,
                description: 'Recently saved JSON snippets',
                data: fn () => $this->getRecentSnippets(),
                order: 71,
                scope: 'detail'
            )
        );
    }

    /**
     * Get recently saved snippets for the activity widget.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecentSnippets(): array
    {
        return NucleusSnippet::forUser(Auth::user())
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($snippet) {
                return [
                    'id' => $snippet->id,
                    'title' => $snippet->title,
                    'timestamp' => $snippet->created_at->diffForHumans(),
                    'icon' => 'Braces',
                    'iconColor' => 'bg-emerald-500',
                ];
            })
            ->toArray();
    }
}

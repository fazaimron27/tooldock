<?php

/**
 * QuickDraw Observer.
 *
 * Clears QuickDraw dashboard widgets when canvas activity changes.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\QuickDraw\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use Modules\QuickDraw\Models\QuickDraw;

class QuickDrawObserver
{
    public function __construct(
        private DashboardWidgetRegistry $dashboardWidgetRegistry
    ) {}

    /**
     * Handle the QuickDraw "saved" event.
     */
    public function saved(QuickDraw $quickDraw): void
    {
        if (! $quickDraw->wasChanged() && ! $quickDraw->wasRecentlyCreated) {
            return;
        }

        $this->clearWidgetCache();
    }

    /**
     * Handle the QuickDraw "deleted" event.
     */
    public function deleted(QuickDraw $quickDraw): void
    {
        $this->clearWidgetCache();
    }

    private function clearWidgetCache(): void
    {
        $this->dashboardWidgetRegistry->clearCache(null, 'QuickDraw');
    }
}

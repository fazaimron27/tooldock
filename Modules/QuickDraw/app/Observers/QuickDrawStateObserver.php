<?php

/**
 * QuickDraw State Observer.
 *
 * Clears QuickDraw dashboard widgets after editor autosaves.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\QuickDraw\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use Modules\QuickDraw\Models\QuickDrawState;

class QuickDrawStateObserver
{
    public function __construct(
        private DashboardWidgetRegistry $dashboardWidgetRegistry
    ) {}

    /**
     * Handle the QuickDrawState "saved" event.
     */
    public function saved(QuickDrawState $quickDrawState): void
    {
        $this->dashboardWidgetRegistry->clearCache(null, 'QuickDraw');
    }
}

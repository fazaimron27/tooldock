<?php

/**
 * Folio Observer.
 *
 * Clears Folio dashboard widgets when resume activity changes.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Folio\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Folio\Models\Folio;

class FolioObserver
{
    public function __construct(
        private DashboardWidgetRegistry $dashboardWidgetRegistry
    ) {}

    /**
     * Handle the Folio "saved" event.
     */
    public function saved(Folio $folio): void
    {
        if (! $folio->wasChanged() && ! $folio->wasRecentlyCreated) {
            return;
        }

        $this->clearWidgetCache();
    }

    /**
     * Handle the Folio "deleted" event.
     */
    public function deleted(Folio $folio): void
    {
        $this->clearWidgetCache();
    }

    private function clearWidgetCache(): void
    {
        $this->dashboardWidgetRegistry->clearCache(null, 'Folio');
    }
}

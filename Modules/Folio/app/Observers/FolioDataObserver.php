<?php

/**
 * Folio Data Observer.
 *
 * Clears Folio dashboard widgets after editor autosaves.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Folio\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Folio\Models\FolioData;

class FolioDataObserver
{
    public function __construct(
        private DashboardWidgetRegistry $dashboardWidgetRegistry
    ) {}

    /**
     * Handle the FolioData "saved" event.
     */
    public function saved(FolioData $folioData): void
    {
        $this->dashboardWidgetRegistry->clearCache(null, 'Folio');
    }
}

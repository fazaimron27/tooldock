<?php

namespace Modules\Newsletter\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Newsletter\Models\Campaign;

/**
 * Observer for Campaign model events.
 *
 * Automatically clears dashboard widget cache when campaigns are modified
 * to ensure widgets reflect real-time data.
 */
class CampaignObserver
{
    /**
     * Handle the Campaign "created" event.
     */
    public function created(Campaign $campaign): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Handle the Campaign "updated" event.
     */
    public function updated(Campaign $campaign): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Handle the Campaign "deleted" event.
     */
    public function deleted(Campaign $campaign): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Clear Newsletter module widget cache.
     */
    private function clearWidgetCache(): void
    {
        app(DashboardWidgetRegistry::class)->clearCache(null, 'Newsletter');
    }
}

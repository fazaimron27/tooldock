<?php

namespace Modules\Media\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use Modules\Media\Models\MediaFile;

/**
 * Observer for MediaFile model events.
 *
 * Automatically clears dashboard widget cache when media files are modified
 * to ensure widgets reflect real-time data.
 */
class MediaFileObserver
{
    /**
     * Handle the MediaFile "created" event.
     */
    public function created(MediaFile $mediaFile): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Handle the MediaFile "updated" event.
     */
    public function updated(MediaFile $mediaFile): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Handle the MediaFile "deleted" event.
     */
    public function deleted(MediaFile $mediaFile): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Clear Media module widget cache.
     */
    private function clearWidgetCache(): void
    {
        app(DashboardWidgetRegistry::class)->clearCache(null, 'Media');
    }
}

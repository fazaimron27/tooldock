<?php

/**
 * Media File Observer.
 *
 * Clears dashboard widget cache when media files are modified
 * to ensure widgets reflect real-time data.
 *
 * @author Tool Dock Team
 * @license MIT
 */

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
     *
     * @param  MediaFile  $mediaFile
     * @return void
     */
    public function created(MediaFile $mediaFile): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Handle the MediaFile "updated" event.
     *
     * @param  MediaFile  $mediaFile
     * @return void
     */
    public function updated(MediaFile $mediaFile): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Handle the MediaFile "deleted" event.
     *
     * @param  MediaFile  $mediaFile
     * @return void
     */
    public function deleted(MediaFile $mediaFile): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Clear Media module widget cache.
     *
     * @return void
     */
    private function clearWidgetCache(): void
    {
        app(DashboardWidgetRegistry::class)->clearCache(null, 'Media');
    }
}

<?php

namespace Modules\Blog\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Log;
use Modules\Blog\Models\Post;

/**
 * Observer for Post model events.
 *
 * Automatically clears dashboard widget cache when posts are modified
 * to ensure widgets reflect real-time data.
 */
class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        $this->clearWidgetCache();
    }

    /**
     * Clear Blog module widget cache.
     */
    private function clearWidgetCache(): void
    {
        try {
            $registry = app(DashboardWidgetRegistry::class);
            $registry->clearCache(null, 'Blog');

            Log::debug('PostObserver: Cleared Blog widget cache', [
                'module' => 'Blog',
            ]);
        } catch (\Throwable $e) {
            Log::error('PostObserver: Failed to clear widget cache', [
                'module' => 'Blog',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

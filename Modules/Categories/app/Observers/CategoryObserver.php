<?php

namespace Modules\Categories\Observers;

use App\Services\Cache\CacheService;
use Modules\Categories\Models\Category;

/**
 * Observer for Category model events.
 *
 * Clears category cache when categories are modified to ensure
 * dropdown lists and other cached category data stay up-to-date.
 */
class CategoryObserver
{
    private const CACHE_TAG = 'categories';

    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * Clear category-related cache.
     */
    private function clearCache(): void
    {
        $this->cacheService->clearTag(self::CACHE_TAG, 'CategoryObserver');
    }
}

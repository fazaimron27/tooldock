<?php

/**
 * Category Observer.
 *
 * Observes Category model events and clears category-related
 * cache when categories are created, updated, or deleted.
 *
 * @author Tool Dock Team
 * @license MIT
 */

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
     *
     * @param  Category  $category  The created category
     * @return void
     */
    public function created(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Category "updated" event.
     *
     * @param  Category  $category  The updated category
     * @return void
     */
    public function updated(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Category "deleted" event.
     *
     * @param  Category  $category  The deleted category
     * @return void
     */
    public function deleted(Category $category): void
    {
        $this->clearCache();
    }

    /**
     * Clear category-related cache.
     *
     * @return void
     */
    private function clearCache(): void
    {
        $this->cacheService->clearTag(self::CACHE_TAG, 'CategoryObserver');
    }
}

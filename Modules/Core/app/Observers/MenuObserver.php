<?php

namespace Modules\Core\App\Observers;

use App\Services\Registry\MenuRegistry;
use Modules\Core\App\Models\Menu;

/**
 * Observer for Menu model events.
 *
 * Clears menu cache when menus are modified directly (e.g., via admin UI).
 */
class MenuObserver
{
    /**
     * Handle the Menu "created" event.
     */
    public function created(Menu $menu): void
    {
        app(MenuRegistry::class)->clearCache();
    }

    /**
     * Handle the Menu "updated" event.
     */
    public function updated(Menu $menu): void
    {
        app(MenuRegistry::class)->clearCache();
    }

    /**
     * Handle the Menu "deleted" event.
     */
    public function deleted(Menu $menu): void
    {
        app(MenuRegistry::class)->clearCache();
    }
}

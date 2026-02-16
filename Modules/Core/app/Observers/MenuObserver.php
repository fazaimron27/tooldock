<?php

/**
 * Menu Observer.
 *
 * Observes menu model events to manage slug generation
 * and cache invalidation for navigation menus.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Observers;

use App\Services\Registry\MenuRegistry;
use Modules\Core\Models\Menu;

/**
 * Observer for Menu model events.
 *
 * Clears menu cache when menus are modified directly (e.g., via admin UI).
 */
class MenuObserver
{
    public function __construct(
        private MenuRegistry $menuRegistry
    ) {}

    /**
     * Handle the Menu "created" event.
     *
     * @param  Menu  $menu  The newly created menu instance
     * @return void
     */
    public function created(Menu $menu): void
    {
        $this->menuRegistry->clearCache();
    }

    /**
     * Handle the Menu "updated" event.
     *
     * @param  Menu  $menu  The updated menu instance
     * @return void
     */
    public function updated(Menu $menu): void
    {
        $this->menuRegistry->clearCache();
    }

    /**
     * Handle the Menu "deleted" event.
     *
     * @param  Menu  $menu  The deleted menu instance
     * @return void
     */
    public function deleted(Menu $menu): void
    {
        $this->menuRegistry->clearCache();
    }
}

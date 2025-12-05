<?php

namespace Modules\Core\App\Observers;

use App\Services\Registry\DashboardWidgetRegistry;
use App\Services\Registry\GroupRegistry;
use App\Services\Registry\MenuRegistry;
use Illuminate\Support\Facades\Log;
use Modules\Core\App\Models\User;
use Modules\Core\App\Services\PermissionCacheService;

/**
 * Observer for User model events.
 *
 * Automatically assigns default Guest group to newly created users
 * if they don't already have any groups assigned.
 */
class UserObserver
{
    public function __construct(
        private PermissionCacheService $permissionCacheService,
        private MenuRegistry $menuRegistry,
        private DashboardWidgetRegistry $dashboardWidgetRegistry,
        private GroupRegistry $groupRegistry
    ) {}

    /**
     * Handle the User "created" event.
     *
     * Assigns the default Guest group (configured in core config) to new users
     * if they don't already have any groups assigned.
     */
    public function created(User $user): void
    {
        if (! $user->groups()->exists()) {
            try {
                $defaultGroupName = config('core.default_group', 'Guest');
                $defaultGroup = $this->groupRegistry->getGroup($defaultGroupName);

                if (! $defaultGroup) {
                    $defaultGroup = \Modules\Groups\Models\Group::where('name', $defaultGroupName)->first();
                }

                if ($defaultGroup) {
                    $user->groups()->attach($defaultGroup->id);
                    $this->permissionCacheService->clear();
                    $this->menuRegistry->clearCacheForUser($user->id);
                    $user->load('groups');
                }

                $this->dashboardWidgetRegistry->clearCache(null, 'Core');
            } catch (\Exception $e) {
                Log::warning('Failed to assign default group to user: '.$e->getMessage(), [
                    'user_id' => $user->id,
                    'group' => $defaultGroupName ?? 'unknown',
                ]);
            }
        }
    }
}

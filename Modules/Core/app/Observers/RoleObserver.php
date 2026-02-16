<?php

/**
 * Role Observer.
 *
 * Observes role model events to invalidate and warm
 * the permission cache when roles are modified.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Observers;

use App\Services\Registry\MenuRegistry;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Jobs\CreateAuditLogJob;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\Role;
use Modules\Core\Services\PermissionCacheService;
use Modules\Groups\Services\GroupPermissionCacheService;

class RoleObserver
{
    public function __construct(
        private MenuRegistry $menuRegistry,
        private PermissionCacheService $permissionCacheService,
        private GroupPermissionCacheService $groupPermissionCacheService
    ) {}

    /**
     * Handle the Role "created" event.
     *
     * Prevents infinite loop if Role model uses LogsActivity trait.
     *
     * @param  Role  $role  The newly created role instance
     * @return void
     */
    public function created(Role $role): void
    {
        if (in_array(LogsActivity::class, class_uses_recursive($role))) {
            return;
        }

        CreateAuditLogJob::dispatch(
            event: AuditLogEvent::CREATED,
            model: $role,
            oldValues: null,
            newValues: $role->getAttributes(),
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent(),
            tags: 'role,permission'
        );

        cache()->forget('core:roles:all');
    }

    /**
     * Handle the Role "updated" event.
     *
     * Prevents infinite loop if Role model uses LogsActivity trait.
     * Clears menu cache for all users with this role if the role name changed.
     *
     * @param  Role  $role  The updated role instance
     * @return void
     */
    public function updated(Role $role): void
    {
        if (in_array(LogsActivity::class, class_uses_recursive($role))) {
            return;
        }

        $dirty = $role->getDirty();

        if (empty($dirty)) {
            return;
        }

        $oldValues = [];
        $original = $role->getOriginal();

        foreach ($dirty as $key => $value) {
            $oldValues[$key] = $original[$key] ?? null;
        }

        CreateAuditLogJob::dispatch(
            event: AuditLogEvent::UPDATED,
            model: $role,
            oldValues: $oldValues,
            newValues: $dirty,
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent(),
            tags: 'role,permission'
        );

        if (isset($dirty['name'])) {
            $role->load('users');
            foreach ($role->users as $user) {
                $this->menuRegistry->clearCacheForUser($user->id);
                $this->groupPermissionCacheService->clearForUser($user->id);
            }
        }

        cache()->forget('core:roles:all');
    }

    /**
     * Handle the Role "deleted" event.
     *
     * Prevents infinite loop if Role model uses LogsActivity trait.
     * Clears menu cache for all users who had this role.
     *
     * @param  Role  $role  The deleted role instance
     * @return void
     */
    public function deleted(Role $role): void
    {
        if (in_array(LogsActivity::class, class_uses_recursive($role))) {
            return;
        }

        CreateAuditLogJob::dispatch(
            event: AuditLogEvent::DELETED,
            model: $role,
            oldValues: $role->getAttributes(),
            newValues: null,
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent(),
            tags: 'role,permission'
        );

        $this->menuRegistry->clearCache();
        $this->groupPermissionCacheService->clear();

        cache()->forget('core:roles:all');
    }

    /**
     * Handle the Role "saved" event (fires after created/updated).
     *
     * Warms the permission cache after Spatie's RefreshesPermissionCache trait
     * clears it, ensuring the cache is immediately available for subsequent requests.
     *
     * @param  Role  $role  The saved role instance
     * @return void
     */
    public function saved(Role $role): void
    {
        $this->permissionCacheService->warm();
    }
}

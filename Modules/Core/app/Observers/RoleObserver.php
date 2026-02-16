<?php

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

        // Invalidate the cached roles list
        cache()->forget('core:roles:all');
    }

    /**
     * Handle the Role "updated" event.
     *
     * Prevents infinite loop if Role model uses LogsActivity trait.
     * Clears menu cache for all users with this role if the role name changed.
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

        // Invalidate the cached roles list
        cache()->forget('core:roles:all');
    }

    /**
     * Handle the Role "deleted" event.
     *
     * Prevents infinite loop if Role model uses LogsActivity trait.
     * Clears menu cache for all users who had this role.
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

        // Invalidate the cached roles list
        cache()->forget('core:roles:all');
    }

    /**
     * Handle the Role "saved" event (fires after created/updated).
     *
     * Warms the permission cache after Spatie's RefreshesPermissionCache trait
     * clears it, ensuring the cache is immediately available for subsequent requests.
     */
    public function saved(Role $role): void
    {
        // Spatie's RefreshesPermissionCache has already cleared the cache at this point.
        // We warm it immediately to avoid slow first requests.
        $this->permissionCacheService->warm();
    }
}

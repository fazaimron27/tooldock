<?php

namespace Modules\Core\Observers;

use App\Services\Registry\MenuRegistry;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Jobs\CreateAuditLogJob;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\Role;

class RoleObserver
{
    public function __construct(
        private MenuRegistry $menuRegistry
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
            }
        }
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
    }
}

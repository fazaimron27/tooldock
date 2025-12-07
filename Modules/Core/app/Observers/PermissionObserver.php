<?php

namespace Modules\Core\Observers;

use App\Services\Registry\MenuRegistry;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Jobs\CreateAuditLogJob;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Core\Models\Permission;

class PermissionObserver
{
    public function __construct(
        private MenuRegistry $menuRegistry
    ) {}

    /**
     * Handle the Permission "created" event.
     *
     * Prevents infinite loop if Permission model uses LogsActivity trait.
     */
    public function created(Permission $permission): void
    {
        if (in_array(LogsActivity::class, class_uses_recursive($permission))) {
            return;
        }

        CreateAuditLogJob::dispatch(
            event: AuditLogEvent::CREATED,
            model: $permission,
            oldValues: null,
            newValues: $permission->getAttributes(),
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent(),
            tags: 'permission'
        );
    }

    /**
     * Handle the Permission "updated" event.
     *
     * Prevents infinite loop if Permission model uses LogsActivity trait.
     * Clears menu cache if permission name changed.
     */
    public function updated(Permission $permission): void
    {
        if (in_array(LogsActivity::class, class_uses_recursive($permission))) {
            return;
        }

        $dirty = $permission->getDirty();

        if (empty($dirty)) {
            return;
        }

        $oldValues = [];
        $original = $permission->getOriginal();

        foreach ($dirty as $key => $value) {
            $oldValues[$key] = $original[$key] ?? null;
        }

        CreateAuditLogJob::dispatch(
            event: AuditLogEvent::UPDATED,
            model: $permission,
            oldValues: $oldValues,
            newValues: $dirty,
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent(),
            tags: 'permission'
        );

        if (isset($dirty['name'])) {
            $this->menuRegistry->clearCache();
        }
    }

    /**
     * Handle the Permission "deleted" event.
     *
     * Prevents infinite loop if Permission model uses LogsActivity trait.
     * Clears all menu caches since any user/role/group could have had this permission.
     */
    public function deleted(Permission $permission): void
    {
        if (in_array(LogsActivity::class, class_uses_recursive($permission))) {
            return;
        }

        CreateAuditLogJob::dispatch(
            event: AuditLogEvent::DELETED,
            model: $permission,
            oldValues: $permission->getAttributes(),
            newValues: null,
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent(),
            tags: 'permission'
        );

        $this->menuRegistry->clearCache();
    }
}

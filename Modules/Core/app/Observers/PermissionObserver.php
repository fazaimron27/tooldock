<?php

namespace Modules\Core\App\Observers;

use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\App\Jobs\CreateAuditLogJob;
use Modules\AuditLog\App\Traits\LogsActivity;
use Spatie\Permission\Models\Permission;

class PermissionObserver
{
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
            event: 'created',
            model: $permission,
            oldValues: null,
            newValues: $permission->getAttributes(),
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent()
        );
    }

    /**
     * Handle the Permission "updated" event.
     *
     * Prevents infinite loop if Permission model uses LogsActivity trait.
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
            event: 'updated',
            model: $permission,
            oldValues: $oldValues,
            newValues: $dirty,
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent()
        );
    }

    /**
     * Handle the Permission "deleted" event.
     *
     * Prevents infinite loop if Permission model uses LogsActivity trait.
     */
    public function deleted(Permission $permission): void
    {
        if (in_array(LogsActivity::class, class_uses_recursive($permission))) {
            return;
        }

        CreateAuditLogJob::dispatch(
            event: 'deleted',
            model: $permission,
            oldValues: $permission->getAttributes(),
            newValues: null,
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent()
        );
    }
}

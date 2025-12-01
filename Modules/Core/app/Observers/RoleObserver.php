<?php

namespace Modules\Core\App\Observers;

use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\App\Jobs\CreateAuditLogJob;
use Modules\AuditLog\App\Traits\LogsActivity;
use Spatie\Permission\Models\Role;

class RoleObserver
{
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
            event: 'created',
            model: $role,
            oldValues: null,
            newValues: $role->getAttributes(),
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent()
        );
    }

    /**
     * Handle the Role "updated" event.
     *
     * Prevents infinite loop if Role model uses LogsActivity trait.
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
            event: 'updated',
            model: $role,
            oldValues: $oldValues,
            newValues: $dirty,
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent()
        );
    }

    /**
     * Handle the Role "deleted" event.
     *
     * Prevents infinite loop if Role model uses LogsActivity trait.
     */
    public function deleted(Role $role): void
    {
        if (in_array(LogsActivity::class, class_uses_recursive($role))) {
            return;
        }

        CreateAuditLogJob::dispatch(
            event: 'deleted',
            model: $role,
            oldValues: $role->getAttributes(),
            newValues: null,
            userId: Auth::id(),
            url: request()?->url(),
            ipAddress: request()?->ip(),
            userAgent: request()?->userAgent()
        );
    }
}

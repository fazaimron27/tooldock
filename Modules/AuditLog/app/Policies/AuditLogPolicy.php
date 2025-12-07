<?php

namespace Modules\AuditLog\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AuditLog\Models\AuditLog;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;

class AuditLogPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('auditlog.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasPermissionTo('auditlog.view');
    }
}

<?php

/**
 * Audit Log Authorization Policy.
 *
 * Defines access control rules for viewing and managing audit log entries,
 * restricting access based on user permissions.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\AuditLog\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AuditLog\Models\AuditLog;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;

/**
 * Audit Log Authorization Policy.
 *
 * Defines access control rules for viewing audit log entries.
 */
class AuditLogPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user  The authenticated user
     * @return bool True if the user has the 'auditlog.view' permission
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('auditlog.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user  The authenticated user
     * @param  AuditLog  $auditLog  The audit log entry to view
     * @return bool True if the user has the 'auditlog.view' permission
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->hasPermissionTo('auditlog.view');
    }
}

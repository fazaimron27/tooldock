<?php

/**
 * Notification Policy
 *
 * Authorization policy for notification-related actions in the Signal module.
 * Enforces strict ownership rules - users can only access their own notifications.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;

/**
 * Class NotificationPolicy
 *
 * Policy for notification authorization with ownership verification.
 * All operations require both permission checks and ownership verification
 * to ensure users cannot access other users' notifications.
 *
 * Permissions used:
 * - notifications.signal.view: Required for viewing notifications
 * - notifications.signal.manage: Required for updating/deleting notifications
 *
 * @see \Modules\Signal\Services\SignalPermissionRegistrar For permission registration
 */
class NotificationPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any notifications.
     *
     * Allows access to the notifications index page.
     * Users can only see their own notifications.
     *
     * @param  User  $user  The authenticated user
     * @return bool True if user has view permission
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('notifications.signal.view');
    }

    /**
     * Determine whether the user can view a specific notification.
     *
     * Verifies both ownership (notifiable_id matches user id) and
     * view permission before allowing access.
     *
     * @param  User  $user  The authenticated user
     * @param  DatabaseNotification  $notification  The notification to view
     * @return bool True if user owns the notification and has view permission
     */
    public function view(User $user, DatabaseNotification $notification): bool
    {
        if ($notification->notifiable_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('notifications.signal.view');
    }

    /**
     * Determine whether the user can update (mark as read) a notification.
     *
     * Verifies both ownership and manage permission before allowing
     * the notification status to be changed.
     *
     * @param  User  $user  The authenticated user
     * @param  DatabaseNotification  $notification  The notification to update
     * @return bool True if user owns the notification and has manage permission
     */
    public function update(User $user, DatabaseNotification $notification): bool
    {
        if ($notification->notifiable_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('notifications.signal.manage');
    }

    /**
     * Determine whether the user can delete a notification.
     *
     * Verifies both ownership and manage permission before allowing
     * the notification to be permanently removed.
     *
     * @param  User  $user  The authenticated user
     * @param  DatabaseNotification  $notification  The notification to delete
     * @return bool True if user owns the notification and has manage permission
     */
    public function delete(User $user, DatabaseNotification $notification): bool
    {
        if ($notification->notifiable_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('notifications.signal.manage');
    }
}

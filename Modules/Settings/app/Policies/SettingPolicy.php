<?php

/**
 * Setting Policy.
 *
 * Authorizes setting operations including viewing
 * and updating application settings.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Settings\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Settings\Models\Setting;

class SettingPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('settings.config.view');
    }

    /**
     * Determine whether the user can update settings (class-level check).
     *
     * Used when no specific setting instance is available.
     *
     * @param  User  $user
     * @return bool
     */
    public function updateAny(User $user): bool
    {
        return $user->hasPermissionTo('settings.config.update');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  Setting  $setting
     * @return bool
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('settings.config.update');
    }
}

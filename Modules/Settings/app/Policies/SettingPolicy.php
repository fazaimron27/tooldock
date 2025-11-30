<?php

namespace Modules\Settings\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasSuperAdminBypass;
use Modules\Settings\Models\Setting;

class SettingPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('settings.config.view');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('settings.config.update');
    }
}

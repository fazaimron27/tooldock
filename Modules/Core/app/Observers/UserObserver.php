<?php

namespace Modules\Core\App\Observers;

use Illuminate\Support\Facades\Log;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Observer for User model events.
 *
 * Automatically assigns default role to newly created users
 * if they don't already have any roles assigned.
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * Assigns the default role (configured in core config) to new users
     * if they don't already have any roles.
     */
    public function created(User $user): void
    {
        if (! $user->roles()->exists()) {
            try {
                $defaultRoleName = config('core.default_role', Roles::STAFF);
                $defaultRole = Role::where('name', $defaultRoleName)->first();

                if ($defaultRole) {
                    $user->assignRole($defaultRole);
                    $user->load('roles', 'permissions');
                    app(PermissionRegistrar::class)->forgetCachedPermissions();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to assign default role to user: '.$e->getMessage(), [
                    'user_id' => $user->id,
                    'role' => $defaultRoleName ?? 'unknown',
                ]);
            }
        }
    }
}

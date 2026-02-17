<?php

/**
 * Habit Policy
 *
 * Authorization policy for Habit model actions.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Routine\Models\Habit;

/**
 * Class HabitPolicy
 *
 * Determines authorization for CRUD operations on habits.
 * Enforces user ownership and permission-based access.
 */
class HabitPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any habits.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('routines.routine.view');
    }

    /**
     * Determine whether the user can view the habit.
     *
     * @param  User  $user
     * @param  Habit  $habit
     * @return bool
     */
    public function view(User $user, Habit $habit): bool
    {
        if ($habit->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('routines.routine.view');
    }

    /**
     * Determine whether the user can create habits.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('routines.routine.create');
    }

    /**
     * Determine whether the user can update the habit.
     *
     * @param  User  $user
     * @param  Habit  $habit
     * @return bool
     */
    public function update(User $user, Habit $habit): bool
    {
        if ($habit->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('routines.routine.edit');
    }

    /**
     * Determine whether the user can delete the habit.
     *
     * @param  User  $user
     * @param  Habit  $habit
     * @return bool
     */
    public function delete(User $user, Habit $habit): bool
    {
        if ($habit->user_id !== $user->id) {
            return false;
        }

        return $user->hasPermissionTo('routines.routine.delete');
    }
}

<?php

/**
 * QuickDraw Policy
 *
 * Authorization policy for QuickDraw canvas operations.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\QuickDraw\Policies;

use Modules\Core\Models\User;
use Modules\QuickDraw\Models\QuickDraw;

/**
 * Class QuickDrawPolicy
 *
 * Defines authorization rules for QuickDraw canvas access.
 * Users can only access their own canvases unless they are Super Admins.
 */
class QuickDrawPolicy
{
    /**
     * Determine whether the user can view any canvases.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('quickdraw.draw.view');
    }

    /**
     * Determine whether the user can view the canvas.
     *
     * @param  User  $user
     * @param  QuickDraw  $quickdraw
     * @return bool
     */
    public function view(User $user, QuickDraw $quickdraw): bool
    {
        return $user->can('quickdraw.draw.view')
            && $quickdraw->user_id === $user->id;
    }

    /**
     * Determine whether the user can create canvases.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('quickdraw.draw.create');
    }

    /**
     * Determine whether the user can update the canvas.
     *
     * @param  User  $user
     * @param  QuickDraw  $quickdraw
     * @return bool
     */
    public function update(User $user, QuickDraw $quickdraw): bool
    {
        return $user->can('quickdraw.draw.edit')
            && $quickdraw->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the canvas.
     *
     * @param  User  $user
     * @param  QuickDraw  $quickdraw
     * @return bool
     */
    public function delete(User $user, QuickDraw $quickdraw): bool
    {
        return $user->can('quickdraw.draw.delete')
            && $quickdraw->user_id === $user->id;
    }
}

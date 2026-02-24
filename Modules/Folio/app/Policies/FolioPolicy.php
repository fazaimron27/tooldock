<?php

/**
 * Folio Policy
 *
 * Authorization policy for Folio resume operations.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Policies;

use Modules\Core\Models\User;
use Modules\Folio\Models\Folio;

/**
 * Class FolioPolicy
 *
 * Defines authorization rules for Folio resume access.
 * Users can only access their own resumes unless they are Super Admins.
 */
class FolioPolicy
{
    /**
     * Determine whether the user can view any resumes.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('folio.folio.view');
    }

    /**
     * Determine whether the user can view the resume.
     *
     * @param  User  $user
     * @param  Folio  $folio
     * @return bool
     */
    public function view(User $user, Folio $folio): bool
    {
        return $user->can('folio.folio.view')
            && $folio->user_id === $user->id;
    }

    /**
     * Determine whether the user can create resumes.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('folio.folio.create');
    }

    /**
     * Determine whether the user can update the resume.
     *
     * @param  User  $user
     * @param  Folio  $folio
     * @return bool
     */
    public function update(User $user, Folio $folio): bool
    {
        return $user->can('folio.folio.edit')
            && $folio->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the resume.
     *
     * @param  User  $user
     * @param  Folio  $folio
     * @return bool
     */
    public function delete(User $user, Folio $folio): bool
    {
        return $user->can('folio.folio.delete')
            && $folio->user_id === $user->id;
    }
}

<?php

/**
 * Media File Policy.
 *
 * Authorizes media file operations with ownership checks
 * via polymorphic parent model relationships.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Media\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;
use Modules\Media\Models\MediaFile;

class MediaFilePolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     *
     * Note: The actual list is filtered by ownership in the controller.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('media.files.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * Checks parent model ownership via polymorphic relation.
     *
     * @param  User  $user
     * @param  MediaFile  $mediaFile
     * @return bool
     */
    public function view(User $user, MediaFile $mediaFile): bool
    {
        if (! $user->hasPermissionTo('media.files.view')) {
            return false;
        }

        return $this->checkOwnership($user, $mediaFile);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('media.files.upload');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Checks parent model ownership via polymorphic relation.
     *
     * @param  User  $user
     * @param  MediaFile  $mediaFile
     * @return bool
     */
    public function update(User $user, MediaFile $mediaFile): bool
    {
        if (! $user->hasPermissionTo('media.files.edit')) {
            return false;
        }

        return $this->checkOwnership($user, $mediaFile);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Checks parent model ownership via polymorphic relation.
     *
     * @param  User  $user
     * @param  MediaFile  $mediaFile
     * @return bool
     */
    public function delete(User $user, MediaFile $mediaFile): bool
    {
        if (! $user->hasPermissionTo('media.files.delete')) {
            return false;
        }

        return $this->checkOwnership($user, $mediaFile);
    }

    /**
     * Check if the user owns the media file via its parent model.
     *
     * Ownership is determined by:
     * 1. If parent model has user_id, compare with current user
     * 2. If parent model IS the user, they own their own attachments
     * 3. Super Admins can access all files
     * 4. Standalone files (no parent) are Super Admin only
     *
     * @param  User  $user
     * @param  MediaFile  $mediaFile
     * @return bool
     */
    private function checkOwnership(User $user, MediaFile $mediaFile): bool
    {
        if ($user->hasRole(Roles::SUPER_ADMIN)) {
            return true;
        }
        if (! $mediaFile->model_type || ! $mediaFile->model_id) {
            return false;
        }

        $parentModel = $mediaFile->model;

        if (! $parentModel) {
            return false;
        }

        if ($parentModel instanceof User) {
            return $parentModel->id === $user->id;
        }
        if (isset($parentModel->user_id)) {
            return $parentModel->user_id === $user->id;
        }

        return false;
    }
}

<?php

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
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('media.files.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * Checks parent model ownership via polymorphic relation.
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
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('media.files.upload');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Checks parent model ownership via polymorphic relation.
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
     */
    private function checkOwnership(User $user, MediaFile $mediaFile): bool
    {
        // Super Admin can access everything
        if ($user->hasRole(Roles::SUPER_ADMIN)) {
            return true;
        }

        // No parent model means standalone file - Super Admin only
        if (! $mediaFile->model_type || ! $mediaFile->model_id) {
            return false;
        }

        // Load the parent model
        $parentModel = $mediaFile->model;

        if (! $parentModel) {
            // Parent model was deleted but file remains - Super Admin only
            return false;
        }

        // If parent model IS the user (e.g., user avatar)
        if ($parentModel instanceof User) {
            return $parentModel->id === $user->id;
        }

        // If parent model has user_id attribute, check ownership
        if (isset($parentModel->user_id)) {
            return $parentModel->user_id === $user->id;
        }

        // Parent model doesn't have ownership concept - deny for safety
        return false;
    }
}

<?php

namespace Modules\Media\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasSuperAdminBypass;
use Modules\Media\Models\MediaFile;

class MediaFilePolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('media.files.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MediaFile $mediaFile): bool
    {
        return $user->hasPermissionTo('media.files.view');
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
     */
    public function update(User $user, MediaFile $mediaFile): bool
    {
        return $user->hasPermissionTo('media.files.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MediaFile $mediaFile): bool
    {
        return $user->hasPermissionTo('media.files.delete');
    }
}

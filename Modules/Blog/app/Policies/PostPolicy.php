<?php

namespace Modules\Blog\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Blog\Models\Post;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasSuperAdminBypass;

class PostPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('blog.posts.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * Super Admins bypass this check via HasSuperAdminBypass trait.
     * Regular users can only view their own posts.
     */
    public function view(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.posts.view')
            && $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('blog.posts.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Super Admins bypass this check via HasSuperAdminBypass trait.
     * Regular users can only update their own posts.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.posts.edit')
            && $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Super Admins bypass this check via HasSuperAdminBypass trait.
     * Regular users can only delete their own posts.
     *
     * Note: Business logic checks (e.g., preventing deletion if used in campaigns)
     * are handled via events in the controller.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.posts.delete')
            && $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can publish the model.
     *
     * Super Admins bypass this check via HasSuperAdminBypass trait.
     * Regular users can only publish their own posts.
     */
    public function publish(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.posts.publish')
            && $user->id === $post->user_id;
    }
}

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
     */
    public function view(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.posts.view');
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
     */
    public function update(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.posts.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.posts.delete');
    }

    /**
     * Determine whether the user can publish the model.
     */
    public function publish(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('blog.posts.publish');
    }
}

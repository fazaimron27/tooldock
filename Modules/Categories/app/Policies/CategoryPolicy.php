<?php

/**
 * Category Policy.
 *
 * Defines authorization rules for viewing, creating, updating,
 * and deleting categories based on user permissions.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Categories\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Categories\Models\Category;
use Modules\Core\Models\User;
use Modules\Core\Traits\HasSuperAdminBypass;

class CategoryPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user  The authenticated user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('categories.category.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user  The authenticated user
     * @param  Category  $category  The category to view
     * @return bool
     */
    public function view(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.category.view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user  The authenticated user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('categories.category.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user  The authenticated user
     * @param  Category  $category  The category to update
     * @return bool
     */
    public function update(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.category.edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user  The authenticated user
     * @param  Category  $category  The category to delete
     * @return bool
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.category.delete');
    }
}

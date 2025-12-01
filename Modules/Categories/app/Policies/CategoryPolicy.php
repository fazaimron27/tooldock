<?php

namespace Modules\Categories\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Categories\Models\Category;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasSuperAdminBypass;

class CategoryPolicy
{
    use HandlesAuthorization, HasSuperAdminBypass;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('categories.category.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.category.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('categories.category.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.category.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.category.delete');
    }
}

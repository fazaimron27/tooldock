<?php

/**
 * Has User Ownership Trait.
 *
 * Provides a query scope to filter records by user ownership,
 * with Super Admins bypassing the ownership restriction.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\User;

/**
 * Trait for models that have user ownership.
 *
 * Provides a reusable `scopeForUser()` method that filters queries
 * by user ownership. All users (including Super Admins) only see
 * their own records.
 *
 * Requirements:
 * - Model must have a `user_id` column
 */
trait HasUserOwnership
{
    /**
     * Scope a query to filter by user ownership.
     *
     * All users only see their own records.
     *
     * @param  Builder  $query
     * @param  User|null  $user
     * @return Builder
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? request()->user();

        return $query->where('user_id', $user?->id);
    }
}

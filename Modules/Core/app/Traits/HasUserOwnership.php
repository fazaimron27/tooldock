<?php

namespace Modules\Core\App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\User;

/**
 * Trait for models that have user ownership.
 *
 * Provides a reusable `scopeForUser()` method that filters queries
 * by user ownership. Super Admins can see all records, regular users
 * only see their own.
 *
 * Requirements:
 * - Model must have a `user_id` column
 */
trait HasUserOwnership
{
    /**
     * Scope a query to filter by user ownership.
     *
     * Super Admins can see all records, regular users only see their own.
     *
     * @param  Builder  $query
     * @param  User|null  $user
     * @return Builder
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? request()->user();

        if ($user?->hasRole(Roles::SUPER_ADMIN)) {
            return $query;
        }

        return $query->where('user_id', $user?->id);
    }
}

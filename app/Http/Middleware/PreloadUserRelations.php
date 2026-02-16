<?php

/**
 * Preload User Relations Middleware
 *
 * Eagerly loads user relations early in the request lifecycle
 * to prevent N+1 query issues during authorization Gate checks
 * such as hasRole() and hasGroupPermission().
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PreloadUserRelations
 *
 * Consolidates relation loading into a single `loadMissing()` call,
 * significantly reducing query count on authenticated routes.
 * Only loads the `groups` relation if the User model defines that
 * relationship, maintaining compatibility when the Groups module
 * is not installed.
 *
 * @see \Illuminate\Auth\Access\Gate For authorization checks that benefit from preloading
 */
class PreloadUserRelations
{
    /**
     * Preload user relations for the current request.
     *
     * Checks for an authenticated user and eagerly loads `roles`
     * (always) and `groups` (if the relationship method exists on
     * the User model). Uses `loadMissing()` to avoid redundant
     * queries if relations are already loaded.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request
     * @param  \Closure  $next  The next middleware in the pipeline
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $relations = ['roles'];

            if (method_exists($user, 'groups')) {
                $relations[] = 'groups';
            }

            $user->loadMissing($relations);
        }

        return $next($request);
    }
}

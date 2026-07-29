<?php

/**
 * NucleusSnippet Policy
 *
 * Handles authorization for NucleusSnippet model actions.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Policies;

use Modules\Core\Models\User;
use Modules\Nucleus\Models\NucleusSnippet;

/**
 * Class NucleusSnippetPolicy
 *
 * Enforces that users can only manage their own snippets.
 */
class NucleusSnippetPolicy
{
    /**
     * Determine whether the user can view any snippets.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('nucleus.snippet.view');
    }

    /**
     * Determine whether the user can view the snippet.
     *
     * @param  User  $user
     * @param  NucleusSnippet  $snippet
     * @return bool
     */
    public function view(User $user, NucleusSnippet $snippet): bool
    {
        return $user->can('nucleus.snippet.view') && $snippet->user_id === $user->id;
    }

    /**
     * Determine whether the user can create snippets.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('nucleus.snippet.create');
    }

    /**
     * Determine whether the user can delete the snippet.
     *
     * @param  User  $user
     * @param  NucleusSnippet  $snippet
     * @return bool
     */
    public function delete(User $user, NucleusSnippet $snippet): bool
    {
        return $user->can('nucleus.snippet.delete') && $snippet->user_id === $user->id;
    }
}

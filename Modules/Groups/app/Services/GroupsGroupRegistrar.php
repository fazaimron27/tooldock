<?php

/**
 * Groups Group Registrar.
 *
 * Handles group registration for the Groups module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Groups\Services;

use App\Services\Registry\GroupRegistry;

/**
 * Handles group registration for the Groups module.
 */
class GroupsGroupRegistrar
{
    /**
     * Register default groups for the Groups module.
     *
     * @param  GroupRegistry  $registry
     * @param  string  $moduleName
     * @return void
     */
    public function register(GroupRegistry $registry, string $moduleName): void
    {
        $registry->register(
            module: $moduleName,
            name: 'Guest',
            description: 'Default group for newly registered users',
            slug: 'guest'
        );
    }

    /**
     * Attach roles to groups after seeding.
     *
     * This is called after groups and roles are seeded to establish relationships.
     *
     * @param  GroupsRoleService  $roleService
     * @return void
     */
    public function attachRolesToGroups(GroupsRoleService $roleService): void
    {
        $roleService->ensureGuestRoleAttached();
    }
}

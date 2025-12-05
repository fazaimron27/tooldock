<?php

namespace Modules\Groups\Services;

use App\Services\Registry\GroupRegistry;

/**
 * Handles group registration for the Groups module.
 */
class GroupsGroupRegistrar
{
    /**
     * Register default groups for the Groups module.
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
}

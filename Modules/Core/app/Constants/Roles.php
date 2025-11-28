<?php

namespace Modules\Core\App\Constants;

/**
 * Role name constants.
 *
 * Centralizes role names used throughout the application
 * to ensure consistency and make refactoring easier.
 */
class Roles
{
    /**
     * Super Admin role name.
     *
     * Users with this role bypass all permission checks
     * via Gate::before() callback.
     */
    public const SUPER_ADMIN = 'Super Admin';

    /**
     * Administrator role name.
     */
    public const ADMINISTRATOR = 'Administrator';

    /**
     * Manager role name.
     */
    public const MANAGER = 'Manager';

    /**
     * Staff role name.
     */
    public const STAFF = 'Staff';

    /**
     * Auditor role name.
     */
    public const AUDITOR = 'Auditor';
}

<?php

/**
 * Role Constants.
 *
 * Defines string constants for default system roles
 * used throughout the application.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Constants;

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

    /**
     * Guest role name.
     */
    public const GUEST = 'Guest';
}

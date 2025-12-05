<?php

use Modules\Core\App\Constants\Roles;

return [
    'name' => 'Core',

    /*
    |--------------------------------------------------------------------------
    | Default User Group
    |--------------------------------------------------------------------------
    |
    | This value determines the default group assigned to newly registered users.
    | Users created through the registration form will automatically receive
    | this group. Users created by administrators can still be assigned
    | different groups during creation.
    |
    | The default group is "Guest" which has no permissions and redirects users
    | to a welcome page instead of the dashboard.
    |
    */
    'default_group' => env('CORE_DEFAULT_GROUP', 'Guest'),

    /*
    |--------------------------------------------------------------------------
    | Default User Role (Deprecated)
    |--------------------------------------------------------------------------
    |
    | DEPRECATED: This setting is kept for backward compatibility but is no
    | longer used. New users are assigned to the default group instead.
    |
    | This value was previously used to assign a default role to newly registered
    | users. The default group system has replaced this functionality.
    |
    */
    'default_role' => env('CORE_DEFAULT_ROLE', Roles::STAFF),

    /*
    |--------------------------------------------------------------------------
    | Super Admin User Credentials
    |--------------------------------------------------------------------------
    |
    | These values are used to create the initial Super Admin user during
    | database seeding. The Super Admin user has full system access and
    | bypasses all permission checks.
    |
    | WARNING: The default password is insecure. Always set SUPER_ADMIN_PASSWORD
    | in your .env file for production environments.
    |
    */
    'super_admin_email' => env('SUPER_ADMIN_EMAIL', 'superadmin@example.com'),
    'super_admin_password' => env('SUPER_ADMIN_PASSWORD', 'password'),
];

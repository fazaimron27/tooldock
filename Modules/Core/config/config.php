<?php

use Modules\Core\App\Constants\Roles;

return [
    'name' => 'Core',

    /*
    |--------------------------------------------------------------------------
    | Default User Role
    |--------------------------------------------------------------------------
    |
    | This value determines the default role assigned to newly registered users.
    | Users created through the registration form will automatically receive
    | this role. Users created by administrators can still be assigned
    | different roles during creation.
    |
    | Available roles:
    | - Roles::STAFF (default) - Basic user with minimal permissions
    | - Roles::MANAGER - User with view users permission
    | - Roles::AUDITOR - User with view users permission
    | - Roles::ADMINISTRATOR - User with full user/role management
    | - Roles::SUPER_ADMIN - Full system access (not recommended for auto-assignment)
    |
    */
    'default_role' => env('CORE_DEFAULT_ROLE', Roles::STAFF),
];

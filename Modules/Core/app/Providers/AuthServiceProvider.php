<?php

/**
 * Auth Service Provider.
 *
 * Registers authorization policies for Core module models
 * and configures Gate super admin bypass.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Core\Policies\RolePolicy;
use Modules\Core\Policies\UserPolicy;

/**
 * Class AuthServiceProvider
 *
 * Maps Core module models to their authorization policies.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}

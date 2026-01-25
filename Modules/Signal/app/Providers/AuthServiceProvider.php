<?php

/**
 * Auth Service Provider
 *
 * Registers authorization policies for the Signal module.
 * Maps model classes to their corresponding policy classes
 * for Laravel's Gate authorization system.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Signal\Policies\NotificationPolicy;

/**
 * Class AuthServiceProvider
 *
 * Extends Laravel's AuthServiceProvider to register policies
 * specific to the Signal module. The NotificationPolicy is bound
 * to Laravel's DatabaseNotification model for authorization.
 *
 * @see \Modules\Signal\Policies\NotificationPolicy
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the module.
     *
     * Maps model classes to their corresponding policy classes.
     * The DatabaseNotification model uses NotificationPolicy for authorization.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        DatabaseNotification::class => NotificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * Calls the parent implementation to register the defined policies.
     * No additional setup is required for the Signal module.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}

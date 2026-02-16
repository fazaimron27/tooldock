<?php

/**
 * Groups Auth Service Provider.
 *
 * Registers policy mappings for group model authorization.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Groups\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Groups\Models\Group;
use Modules\Groups\Policies\GroupPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Group::class => GroupPolicy::class,
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

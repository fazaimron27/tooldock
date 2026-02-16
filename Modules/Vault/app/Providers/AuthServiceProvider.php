<?php

/**
 * Vault Auth Service Provider
 *
 * Registers model-to-policy mappings for the Vault module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Vault\Models\Vault;
use Modules\Vault\Policies\VaultPolicy;

/**
 * Class AuthServiceProvider
 *
 * Maps the Vault model to VaultPolicy for authorization checks.
 *
 * @see \Modules\Vault\Policies\VaultPolicy
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Vault::class => VaultPolicy::class,
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

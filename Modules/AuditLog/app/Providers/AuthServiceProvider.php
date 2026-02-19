<?php

/**
 * Audit Log Auth Service Provider.
 *
 * Registers policy mappings for the AuditLog module,
 * binding the AuditLog model to its authorization policy.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\AuditLog\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\AuditLog\Models\AuditLog;
use Modules\AuditLog\Policies\AuditLogPolicy;

/**
 * Class AuthServiceProvider
 *
 * Maps the AuditLog model to AuditLogPolicy for authorization checks.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AuditLog::class => AuditLogPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}

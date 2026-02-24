<?php

/**
 * Folio Auth Service Provider
 *
 * Registers model-to-policy mappings for the Folio module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Folio\Models\Folio;
use Modules\Folio\Policies\FolioPolicy;

/**
 * Class AuthServiceProvider
 *
 * Maps the Folio model to FolioPolicy for authorization checks.
 *
 * @see \Modules\Folio\Policies\FolioPolicy
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Folio::class => FolioPolicy::class,
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

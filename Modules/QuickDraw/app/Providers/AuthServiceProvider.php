<?php

/**
 * QuickDraw Auth Service Provider
 *
 * Registers model-to-policy mappings for the QuickDraw module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\QuickDraw\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\QuickDraw\Models\QuickDraw;
use Modules\QuickDraw\Policies\QuickDrawPolicy;

/**
 * Class AuthServiceProvider
 *
 * Maps the QuickDraw model to QuickDrawPolicy for authorization checks.
 *
 * @see \Modules\QuickDraw\Policies\QuickDrawPolicy
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        QuickDraw::class => QuickDrawPolicy::class,
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

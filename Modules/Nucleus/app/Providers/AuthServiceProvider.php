<?php

/**
 * Nucleus Auth Service Provider
 *
 * Registers model-to-policy mappings for the Nucleus module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Nucleus\Models\NucleusSnippet;
use Modules\Nucleus\Policies\NucleusSnippetPolicy;

/**
 * Class AuthServiceProvider
 *
 * Maps the NucleusSnippet model to NucleusSnippetPolicy for authorization checks.
 *
 * @see NucleusSnippetPolicy
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        NucleusSnippet::class => NucleusSnippetPolicy::class,
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

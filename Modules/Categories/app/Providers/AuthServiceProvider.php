<?php

/**
 * Categories Auth Service Provider.
 *
 * Registers the Category model policy mapping for the Categories module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Categories\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Categories\Models\Category;
use Modules\Categories\Policies\CategoryPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Category::class => CategoryPolicy::class,
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

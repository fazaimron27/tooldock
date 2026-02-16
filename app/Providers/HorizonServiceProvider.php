<?php

/**
 * Horizon Service Provider
 *
 * Configures Laravel Horizon dashboard access control,
 * restricting visibility to Super Admin users only.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Modules\Core\Constants\Roles;

/**
 * Class HorizonServiceProvider
 *
 * Extends the base Horizon service provider to define authorization
 * rules for accessing the Horizon dashboard. Only users with the
 * Super Admin role are allowed to view the dashboard.
 *
 * @see \Laravel\Horizon\HorizonApplicationServiceProvider
 * @see \Modules\Core\Constants\Roles For role constant definitions
 */
class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * Calls the parent boot method to register Horizon's default
     * routes and configurations.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the Horizon gate authorization.
     *
     * Registers a Gate definition that restricts Horizon dashboard
     * access to users with the Super Admin role.
     *
     * @return void
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return $user?->hasRole(Roles::SUPER_ADMIN) ?? false;
        });
    }

    /**
     * Configure Horizon authorization.
     *
     * Registers the gate and sets up the Horizon auth callback
     * to check the `viewHorizon` gate for every request.
     *
     * @return void
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request) {
            return Gate::check('viewHorizon', [$request->user()]);
        });
    }
}

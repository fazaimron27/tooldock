<?php

/**
 * Route Service Provider
 *
 * Registers and configures routes for the Signal module.
 * Handles both web (Inertia) and API route registration
 * with appropriate middleware and prefixes.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Class RouteServiceProvider
 *
 * Configures routing for the Signal module with proper middleware,
 * prefixes, and namespace organization. Web routes are prefixed with
 * '/tooldock' for application route grouping.
 *
 * Route files:
 * - routes/web.php: Web routes with session/CSRF protection
 * - routes/api.php: API routes (currently placeholder for future use)
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module name for path resolution.
     *
     * @var string
     */
    protected string $name = 'Signal';

    /**
     * Bootstrap any application services.
     *
     * Registers model bindings and pattern-based filters
     * before routes are loaded.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * Called during application bootstrapping to register
     * both API and web routes for the module.
     *
     * @return void
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * Registers routes with 'web' middleware for session state,
     * CSRF protection, and cookie encryption. Routes are prefixed
     * with '/tooldock' for application-wide consistency.
     *
     * @return void
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->prefix('tooldock')
            ->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * Registers stateless API routes with 'api' middleware.
     * Routes are prefixed with '/api' and named with 'api.' prefix.
     * Currently a placeholder for future API endpoints.
     *
     * @return void
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }
}

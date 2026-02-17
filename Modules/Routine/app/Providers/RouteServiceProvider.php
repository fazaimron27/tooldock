<?php

/**
 * Routine Route Service Provider
 *
 * Maps web and API routes for the Routine module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Class RouteServiceProvider
 *
 * Registers web and API route files with appropriate middleware.
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected string $name = 'Routine';

    /**
     * Called before routes are registered.
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
     * @return void
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * @return void
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->name('api.')
            ->group(module_path($this->name, '/routes/api.php'));
    }
}

<?php

/**
 * Categories Event Service Provider.
 *
 * Registers model observers and event listeners for the Categories module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Categories\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Categories\Models\Category;
use Modules\Categories\Observers\CategoryObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Register model observers.
     *
     * @return void
     */
    public function boot(): void
    {
        Category::observe(CategoryObserver::class);
    }

    /**
     * Configure the proper event listeners for email verification.
     *
     * Overridden as a no-op to prevent this module from re-registering
     * the email verification listener that the main app already handles.
     *
     * @return void
     */
    protected function configureEmailVerification(): void {}
}

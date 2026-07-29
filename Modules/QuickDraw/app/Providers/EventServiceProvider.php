<?php

/**
 * QuickDraw Event Service Provider
 *
 * Registers event listeners for the QuickDraw module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\QuickDraw\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\QuickDraw\Models\QuickDraw;
use Modules\QuickDraw\Models\QuickDrawState;
use Modules\QuickDraw\Observers\QuickDrawObserver;
use Modules\QuickDraw\Observers\QuickDrawStateObserver;

/**
 * Class EventServiceProvider
 *
 * Manages event-to-listener bindings for the QuickDraw module.
 * Event discovery is enabled by default.
 */
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
     * Register model observers and configure events.
     */
    public function boot(): void
    {
        parent::boot();

        QuickDraw::observe(QuickDrawObserver::class);
        QuickDrawState::observe(QuickDrawStateObserver::class);
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

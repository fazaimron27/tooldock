<?php

/**
 * Event Service Provider
 *
 * Registers event listeners and subscribers for the Signal module.
 * Currently uses automatic event discovery for flexibility.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Class EventServiceProvider
 *
 * Extends Laravel's EventServiceProvider to provide event handling
 * capabilities for the Signal module. Uses automatic event discovery
 * to detect listener classes without explicit registration.
 *
 * @see https://laravel.com/docs/events#event-discovery Event Discovery Documentation
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the module.
     *
     * Maps event classes to arrays of listener classes.
     * Currently empty as automatic discovery is enabled.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * When true, Laravel will automatically discover listeners
     * based on naming conventions and type hints.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     *
     * Placeholder method for email verification event configuration.
     * Currently not used by the Signal module.
     *
     * @return void
     */
    protected function configureEmailVerification(): void {}
}

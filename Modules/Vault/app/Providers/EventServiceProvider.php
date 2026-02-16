<?php

/**
 * Vault Event Service Provider
 *
 * Registers event listeners for the Vault module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Class EventServiceProvider
 *
 * Manages event-to-listener bindings for the Vault module.
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
     * Configure the proper event listeners for email verification.
     *
     * @return void
     */
    protected function configureEmailVerification(): void {}
}

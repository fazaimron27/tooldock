<?php

/**
 * Event Service Provider.
 *
 * Registers event listeners and model observers
 * for the Core module's domain events.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Core\Providers;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Core\Listeners\SendLockoutNotification;

/**
 * Class EventServiceProvider
 *
 * Manages event-to-listener bindings for the Core module.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        Lockout::class => [
            SendLockoutNotification::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

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

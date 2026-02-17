<?php

/**
 * Routine Event Service Provider
 *
 * Registers event listeners for the Routine module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Routine\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Routine\Models\Habit;
use Modules\Routine\Models\HabitLog;
use Modules\Routine\Observers\HabitLogObserver;
use Modules\Routine\Observers\HabitObserver;

/**
 * Class EventServiceProvider
 *
 * Manages event-to-listener bindings and model observers for the Routine module.
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
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        Habit::observe(HabitObserver::class);
        HabitLog::observe(HabitLogObserver::class);
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

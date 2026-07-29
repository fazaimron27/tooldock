<?php

/**
 * Folio Event Service Provider
 *
 * Registers event listeners and model observers for the Folio module.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Folio\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Folio\Models\Folio;
use Modules\Folio\Models\FolioData;
use Modules\Folio\Observers\FolioDataObserver;
use Modules\Folio\Observers\FolioObserver;

/**
 * Class EventServiceProvider
 *
 * Folio module event listeners and model observer registration.
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

        Folio::observe(FolioObserver::class);
        FolioData::observe(FolioDataObserver::class);
    }

    /**
     * Configure the proper event listeners for email verification.
     *
     * @return void
     */
    protected function configureEmailVerification(): void {}
}

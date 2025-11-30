<?php

namespace Modules\Newsletter\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Blog\Events\PostDeleted;
use Modules\Blog\Events\PostDeleting;
use Modules\Blog\Events\PostUpdating;
use Modules\Newsletter\Listeners\CleanupDraftCampaignsOnPostDeletion;
use Modules\Newsletter\Listeners\PreventPostDeletionIfUsedInCampaigns;
use Modules\Newsletter\Listeners\PreventPostUpdateIfUsedInSendingCampaigns;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        PostDeleting::class => [
            PreventPostDeletionIfUsedInCampaigns::class,
        ],
        PostDeleted::class => [
            CleanupDraftCampaignsOnPostDeletion::class,
        ],
        PostUpdating::class => [
            PreventPostUpdateIfUsedInSendingCampaigns::class,
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
     */
    protected function configureEmailVerification(): void {}
}

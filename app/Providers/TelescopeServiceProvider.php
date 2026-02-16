<?php

/**
 * Telescope Service Provider
 *
 * Configures Laravel Telescope for request monitoring, filtering
 * recorded entries by environment, and restricting dashboard
 * access to Super Admin users only.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Modules\Core\Constants\Roles;

/**
 * Class TelescopeServiceProvider
 *
 * Extends the base Telescope service provider to configure entry
 * filtering (records everything locally, only exceptions and failures
 * in production) and authorization for accessing the dashboard.
 *
 * @see \Laravel\Telescope\TelescopeApplicationServiceProvider
 * @see \Modules\Core\Constants\Roles For role constant definitions
 */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * Configures Telescope entry filtering and hides sensitive
     * request details in non-local environments. In local mode,
     * all entries are recorded. In production, only reportable
     * exceptions, failed requests/jobs, scheduled tasks, and
     * monitored tags are captured.
     *
     * @return void
     */
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                $entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag();
        });
    }

    /**
     * Hide sensitive request details from Telescope.
     *
     * Strips CSRF tokens from request parameters and sensitive
     * headers (cookie, x-csrf-token, x-xsrf-token) from recorded
     * entries. Only applies in non-local environments since full
     * visibility is helpful during development.
     *
     * @return void
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Define the Telescope gate authorization.
     *
     * Registers a Gate definition that restricts Telescope dashboard
     * access to users with the Super Admin role.
     *
     * @return void
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user = null) {
            return $user?->hasRole(Roles::SUPER_ADMIN) ?? false;
        });
    }

    /**
     * Configure Telescope authorization.
     *
     * Registers the gate and sets up the Telescope auth callback
     * to check the `viewTelescope` gate for every request.
     *
     * @return void
     */
    protected function authorization(): void
    {
        $this->gate();

        Telescope::auth(function ($request) {
            return Gate::check('viewTelescope', [$request->user()]);
        });
    }
}

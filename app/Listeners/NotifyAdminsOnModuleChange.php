<?php

/**
 * Notify Admins On Module Change Listener
 *
 * Sends notifications to Super Admin users when modules are
 * installed, uninstalled, enabled, or disabled to provide
 * visibility into system changes.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Listeners;

use App\Events\Modules\ModuleDisabled;
use App\Events\Modules\ModuleEnabled;
use App\Events\Modules\ModuleInstalled;
use App\Events\Modules\ModuleInstalling;
use App\Events\Modules\ModuleUninstalled;
use App\Events\Modules\ModuleUninstalling;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\User;
use Modules\Signal\Jobs\SendNotificationJob;

/**
 * Class NotifyAdminsOnModuleChange
 *
 * Listens for module lifecycle events and notifies Super Admins.
 * Uses cache-based deduplication to prevent duplicate notifications.
 * Skips notifications for protected modules (auto-installed during migrations).
 *
 * @see \App\Events\Modules\ModuleInstalled
 * @see \App\Events\Modules\ModuleUninstalled
 * @see \App\Events\Modules\ModuleEnabled
 * @see \App\Events\Modules\ModuleDisabled
 * @see \Modules\Signal\Jobs\SendNotificationJob For sending queued notifications
 */
class NotifyAdminsOnModuleChange
{
    /**
     * Handle the module installing event.
     *
     * Sets a cache key to prevent duplicate enabled notification
     * since install internally calls enable.
     *
     * @param  ModuleInstalling  $event  The module installing event
     * @return void
     */
    public function handleInstalling(ModuleInstalling $event): void
    {
        Cache::put("module_installing:{$event->moduleName}", true, 60);
    }

    /**
     * Handle the module uninstalling event.
     *
     * Sets a cache key to prevent duplicate disabled notification
     * since uninstall internally calls disable.
     *
     * @param  ModuleUninstalling  $event  The module uninstalling event
     * @return void
     */
    public function handleUninstalling(ModuleUninstalling $event): void
    {
        Cache::put("module_uninstalling:{$event->moduleName}", true, 60);
    }

    /**
     * Handle the module installed event.
     *
     * Sends notifications to all Super Admin users when a module
     * is installed. Uses caching to prevent duplicate notifications.
     *
     * @param  ModuleInstalled  $event  The module installed event
     * @return void
     */
    public function handleInstalled(ModuleInstalled $event): void
    {
        Cache::forget("module_installing:{$event->moduleName}");

        if ($this->isProtectedModule($event->module)) {
            return;
        }

        $cacheKey = "module_installed_notified:{$event->moduleName}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 5);

        $this->notifyAdmins(
            'Module Installed',
            "The module \"{$event->moduleName}\" has been installed and is now active.",
            route('core.modules.index')
        );
    }

    /**
     * Handle the module uninstalled event.
     *
     * Sends notifications to all Super Admin users when a module
     * is uninstalled. Uses caching to prevent duplicate notifications.
     *
     * @param  ModuleUninstalled  $event  The module uninstalled event
     * @return void
     */
    public function handleUninstalled(ModuleUninstalled $event): void
    {
        Cache::forget("module_uninstalling:{$event->moduleName}");

        if ($this->isProtectedModule($event->module)) {
            return;
        }

        $cacheKey = "module_uninstalled_notified:{$event->moduleName}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 5);

        $this->notifyAdmins(
            'Module Uninstalled',
            "The module \"{$event->moduleName}\" has been uninstalled and is no longer available.",
            route('core.modules.index')
        );
    }

    /**
     * Handle the module enabled event.
     *
     * Sends notifications to all Super Admin users when a module
     * is enabled. Uses caching to prevent duplicate notifications.
     * Skips notification if module was just installed (to avoid double notification).
     *
     * @param  ModuleEnabled  $event  The module enabled event
     * @return void
     */
    public function handleEnabled(ModuleEnabled $event): void
    {
        if ($this->isProtectedModule($event->module)) {
            return;
        }

        if (Cache::has("module_installing:{$event->moduleName}")) {
            return;
        }

        $cacheKey = "module_enabled_notified:{$event->moduleName}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 5);

        $this->notifyAdmins(
            'Module Enabled',
            "The module \"{$event->moduleName}\" has been enabled and is now active.",
            route('core.modules.index')
        );
    }

    /**
     * Handle the module disabled event.
     *
     * Sends notifications to all Super Admin users when a module
     * is disabled. Uses caching to prevent duplicate notifications.
     * Skips notification if module was just uninstalled (to avoid double notification).
     *
     * @param  ModuleDisabled  $event  The module disabled event
     * @return void
     */
    public function handleDisabled(ModuleDisabled $event): void
    {
        if ($this->isProtectedModule($event->module)) {
            return;
        }

        if (Cache::has("module_uninstalling:{$event->moduleName}")) {
            return;
        }

        $cacheKey = "module_disabled_notified:{$event->moduleName}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 5);

        $this->notifyAdmins(
            'Module Disabled',
            "The module \"{$event->moduleName}\" has been disabled and is no longer active.",
            route('core.modules.index')
        );
    }

    /**
     * Check if a module is protected (auto-installed, cannot be uninstalled).
     *
     * Protected modules don't trigger notifications since they're
     * part of core functionality and installed automatically.
     *
     * @param  mixed  $module  The module object to check
     * @return bool True if the module is protected
     */
    private function isProtectedModule($module): bool
    {
        try {
            return (bool) $module->get('protected', false);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Notify all Super Admin users.
     *
     * Dispatches queued notifications to each Super Admin user.
     * Action URL is only included if the user has permission.
     *
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string  $actionUrl  URL to module management
     * @return void
     */
    private function notifyAdmins(string $title, string $message, string $actionUrl): void
    {
        try {
            if (! class_exists(SendNotificationJob::class)) {
                return;
            }

            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', Roles::SUPER_ADMIN);
            })->get();

            foreach ($admins as $admin) {
                $url = $admin->can('core.modules.manage') ? $actionUrl : null;

                SendNotificationJob::dispatch(
                    userId: $admin->id,
                    type: 'info',
                    title: $title,
                    message: $message,
                    url: $url,
                    module: 'Platform',
                    category: null
                );
            }
        } catch (\Exception $e) {
            Log::warning('NotifyAdminsOnModuleChange: Failed to dispatch notification jobs', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

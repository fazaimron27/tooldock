<?php

/**
 * Notify Admins On Module Change Listener
 *
 * Sends notifications to Super Admin users when modules are
 * installed or uninstalled to provide visibility into system changes.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Listeners;

use App\Events\Modules\ModuleInstalled;
use App\Events\Modules\ModuleUninstalled;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Constants\Roles;
use Modules\Core\Models\User;
use Modules\Signal\Facades\Signal;

/**
 * Class NotifyAdminsOnModuleChange
 *
 * Listens for module lifecycle events and notifies Super Admins.
 * Uses cache-based deduplication to prevent duplicate notifications.
 * Skips notifications for protected modules (auto-installed during migrations).
 *
 * @see \App\Events\Modules\ModuleInstalled
 * @see \App\Events\Modules\ModuleUninstalled
 * @see \Modules\Signal\Facades\Signal For sending notifications
 */
class NotifyAdminsOnModuleChange
{
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
     * Sends an informational notification to each Super Admin user.
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
            if (! class_exists(Signal::class)) {
                return;
            }

            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', Roles::SUPER_ADMIN);
            })->get();

            foreach ($admins as $admin) {
                $url = $admin->can('core.modules.manage') ? $actionUrl : null;
                Signal::info($admin, $title, $message, $url, 'System');
            }
        } catch (\Exception $e) {
            Log::warning('NotifyAdminsOnModuleChange: Failed to send notifications', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

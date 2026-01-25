<?php

/**
 * Sends Signal Notifications Trait
 *
 * Trait for safely sending Signal notifications from any class.
 * Provides a decoupled way to send notifications that gracefully
 * handles cases where the Signal module is not installed.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Traits;

use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;

/**
 * Trait SendsSignalNotifications
 *
 * Provides convenience methods for sending Signal notifications.
 * Checks for module availability before attempting to send,
 * ensuring no failures if the module is disabled or uninstalled.
 *
 * Usage:
 * ```php
 * class MyController {
 *     use SendsSignalNotifications;
 *
 *     public function store() {
 *         $this->signalSuccess($user, 'Created', 'Item created.');
 *     }
 * }
 * ```
 */
trait SendsSignalNotifications
{
    /**
     * Safely send a Signal notification.
     *
     * Checks if the Signal module is available before sending.
     * Silently logs and returns if the module is not present.
     *
     * @param  User  $user  Target user for the notification
     * @param  string  $type  Notification type (success, info, warning, alert)
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message content
     * @param  string|null  $actionUrl  Optional URL for click action
     * @param  string|null  $module  Source module identifier
     * @param  string|null  $category  Notification category for preferences
     * @return void
     */
    protected function sendSignalNotification(
        $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $module = null,
        ?string $category = null
    ): void {
        if (! class_exists(\Modules\Signal\Facades\Signal::class)) {
            return;
        }

        try {
            $signal = app(\Modules\Signal\Services\SignalService::class);

            match ($type) {
                'success' => $signal->success($user, $title, $message, $actionUrl, $module, $category),
                'info' => $signal->info($user, $title, $message, $actionUrl, $module, $category),
                'warning' => $signal->warning($user, $title, $message, $actionUrl, $module, $category),
                'alert' => $signal->alert($user, $title, $message, $actionUrl, $module, $category),
                default => $signal->info($user, $title, $message, $actionUrl, $module, $category),
            };
        } catch (\Exception $e) {
            Log::debug('Signal notification skipped: '.$e->getMessage());
        }
    }

    /**
     * Send a success notification.
     *
     * @param  User  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string|null  $actionUrl  Optional action URL
     * @param  string|null  $module  Source module identifier
     * @param  string|null  $category  Notification category
     * @return void
     */
    protected function signalSuccess($user, string $title, string $message, ?string $actionUrl = null, ?string $module = null, ?string $category = null): void
    {
        $this->sendSignalNotification($user, 'success', $title, $message, $actionUrl, $module, $category);
    }

    /**
     * Send an info notification.
     *
     * @param  User  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string|null  $actionUrl  Optional action URL
     * @param  string|null  $module  Source module identifier
     * @param  string|null  $category  Notification category
     * @return void
     */
    protected function signalInfo($user, string $title, string $message, ?string $actionUrl = null, ?string $module = null, ?string $category = null): void
    {
        $this->sendSignalNotification($user, 'info', $title, $message, $actionUrl, $module, $category);
    }

    /**
     * Send a warning notification.
     *
     * @param  User  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string|null  $actionUrl  Optional action URL
     * @param  string|null  $module  Source module identifier
     * @param  string|null  $category  Notification category
     * @return void
     */
    protected function signalWarning($user, string $title, string $message, ?string $actionUrl = null, ?string $module = null, ?string $category = null): void
    {
        $this->sendSignalNotification($user, 'warning', $title, $message, $actionUrl, $module, $category);
    }

    /**
     * Send an alert notification.
     *
     * @param  User  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string|null  $actionUrl  Optional action URL
     * @param  string|null  $module  Source module identifier
     * @param  string|null  $category  Notification category
     * @return void
     */
    protected function signalAlert($user, string $title, string $message, ?string $actionUrl = null, ?string $module = null, ?string $category = null): void
    {
        $this->sendSignalNotification($user, 'alert', $title, $message, $actionUrl, $module, $category);
    }
}

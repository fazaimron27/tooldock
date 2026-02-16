<?php

/**
 * Signal Service
 *
 * Main service for sending notifications in the Signal module.
 * Provides a clean API for other modules to send notifications
 * without directly instantiating notification classes.
 *
 * Supports four delivery modes:
 * - silent: Store to database + broadcast (default, updates bell/dropdown, no toast)
 * - flash: Broadcast only, no database storage (shows toast, ephemeral)
 * - trigger: Broadcast with action trigger (toast + action, no storage)
 * - broadcast: Full broadcast - store to DB + toast + action
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Models\User;
use Modules\Signal\Notifications\SystemNotification;

/**
 * Class SignalService
 *
 * Facade backend for sending notifications. Handles preference checking,
 * cache invalidation, and real-time broadcasting automatically.
 *
 * @see \Modules\Signal\Facades\Signal For static access
 */
class SignalService
{
    /**
     * Delivery mode: Store to database + broadcast, no toast (default).
     */
    public const DELIVERY_SILENT = 'silent';

    /**
     * Delivery mode: Broadcast only, shows toast, no database storage.
     */
    public const DELIVERY_FLASH = 'flash';

    /**
     * Delivery mode: Broadcast with action trigger, no database storage.
     */
    public const DELIVERY_TRIGGER = 'trigger';

    /**
     * Delivery mode: Full broadcast - store to DB + toast + action.
     */
    public const DELIVERY_BROADCAST = 'broadcast';

    /**
     * @param  SignalCacheService|null  $cacheService  Cache service for invalidation
     * @param  SignalPreferenceService|null  $preferenceService  Preference checker service
     */
    public function __construct(
        private ?SignalCacheService $cacheService = null,
        private ?SignalPreferenceService $preferenceService = null
    ) {}

    /**
     * Send an informational notification (stored to inbox).
     *
     * @param  Authenticatable  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string|null  $url  Optional action URL
     * @param  string|null  $moduleSource  Source module identifier
     * @param  string|null  $category  Category for preference check
     * @return void
     */
    public function info(Authenticatable $user, string $title, string $message, ?string $url = null, ?string $moduleSource = null, ?string $category = null): void
    {
        $this->sendWithPreferenceCheck($user, $title, $message, 'info', $url, $moduleSource, $category);
    }

    /**
     * Send a success notification (stored to inbox).
     *
     * @param  Authenticatable  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string|null  $url  Optional action URL
     * @param  string|null  $moduleSource  Source module identifier
     * @param  string|null  $category  Category for preference check
     * @return void
     */
    public function success(Authenticatable $user, string $title, string $message, ?string $url = null, ?string $moduleSource = null, ?string $category = null): void
    {
        $this->sendWithPreferenceCheck($user, $title, $message, 'success', $url, $moduleSource, $category);
    }

    /**
     * Send a warning notification (stored to inbox).
     *
     * @param  Authenticatable  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string|null  $url  Optional action URL
     * @param  string|null  $moduleSource  Source module identifier
     * @param  string|null  $category  Category for preference check
     * @return void
     */
    public function warning(Authenticatable $user, string $title, string $message, ?string $url = null, ?string $moduleSource = null, ?string $category = null): void
    {
        $this->sendWithPreferenceCheck($user, $title, $message, 'warning', $url, $moduleSource, $category);
    }

    /**
     * Send an alert/error notification (stored to inbox).
     *
     * @param  Authenticatable  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string|null  $url  Optional action URL
     * @param  string|null  $moduleSource  Source module identifier
     * @param  string|null  $category  Category for preference check
     * @return void
     */
    public function alert(Authenticatable $user, string $title, string $message, ?string $url = null, ?string $moduleSource = null, ?string $category = null): void
    {
        $this->sendWithPreferenceCheck($user, $title, $message, 'error', $url, $moduleSource, $category);
    }

    /**
     * Send a notification with custom type (stored to inbox).
     *
     * @param  Authenticatable  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string  $type  Notification type
     * @param  string|null  $url  Optional action URL
     * @param  string|null  $moduleSource  Source module identifier
     * @param  string|null  $category  Category for preference check
     * @return void
     */
    public function send(Authenticatable $user, string $title, string $message, string $type = 'info', ?string $url = null, ?string $moduleSource = null, ?string $category = null): void
    {
        $this->sendWithPreferenceCheck($user, $title, $message, $type, $url, $moduleSource, $category);
    }

    /**
     * Send a flash notification (toast only, no inbox storage).
     *
     * Use this for transient notifications that don't need to persist.
     * The notification will appear as a toast in the user's browser
     * but will NOT be stored in the database.
     *
     * @param  Authenticatable  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string  $type  Notification type (info, success, warning, error)
     * @param  string|null  $url  Optional action URL
     * @param  string|null  $moduleSource  Source module identifier
     * @return void
     */
    public function flash(Authenticatable $user, string $title, string $message, string $type = 'info', ?string $url = null, ?string $moduleSource = null): void
    {
        if (! $user instanceof User) {
            return;
        }

        $notificationId = uniqid('flash_');

        $this->broadcastNotification(
            user: $user,
            notificationId: $notificationId,
            title: $title,
            message: $message,
            type: $type,
            url: $url,
            moduleSource: $moduleSource,
            delivery: self::DELIVERY_FLASH
        );
    }

    /**
     * Send a trigger notification (triggers frontend action, no storage).
     *
     * Use this to trigger frontend actions like page reload or navigation.
     * The notification will appear as a toast and then execute the action.
     * NOT stored in the database.
     *
     * Common actions:
     * - 'reload_permissions': Forces a full page reload (for permission changes)
     * - 'navigate': Navigate to a URL (requires $url parameter)
     * - 'refresh': Soft refresh via Inertia
     *
     * @param  Authenticatable  $user  Target user
     * @param  string  $action  Action identifier to trigger on frontend
     * @param  string|null  $title  Optional toast title
     * @param  string|null  $message  Optional toast message
     * @param  string  $type  Notification type (info, success, warning, error)
     * @param  string|null  $url  Optional URL (used with 'navigate' action)
     * @param  string|null  $moduleSource  Source module identifier
     * @return void
     */
    public function trigger(
        Authenticatable $user,
        string $action,
        ?string $title = null,
        ?string $message = null,
        string $type = 'info',
        ?string $url = null,
        ?string $moduleSource = null
    ): void {
        if (! $user instanceof User) {
            return;
        }

        $notificationId = uniqid('trigger_');

        $this->broadcastNotification(
            user: $user,
            notificationId: $notificationId,
            title: $title ?? '',
            message: $message ?? '',
            type: $type,
            url: $url,
            moduleSource: $moduleSource,
            delivery: self::DELIVERY_TRIGGER,
            action: $action
        );
    }

    /**
     * Send a broadcast notification (full: inbox + toast + action).
     *
     * Use this when you need everything: persistent storage in inbox,
     * toast notification, AND a frontend action trigger.
     *
     * Ideal for important system changes that need:
     * - A record in the inbox for user reference
     * - Immediate visual feedback via toast
     * - A frontend reaction (like page reload for permission changes)
     *
     * @param  Authenticatable  $user  Target user
     * @param  string  $action  Action identifier to trigger on frontend
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string  $type  Notification type (info, success, warning, error)
     * @param  string|null  $url  Optional action URL
     * @param  string|null  $moduleSource  Source module identifier
     * @param  string|null  $category  Category for preference check
     * @return void
     */
    public function broadcast(
        Authenticatable $user,
        string $action,
        string $title,
        string $message,
        string $type = 'info',
        ?string $url = null,
        ?string $moduleSource = null,
        ?string $category = null
    ): void {
        if ($category !== null && $this->preferenceService !== null && $user instanceof User) {
            if (! $this->preferenceService->isEnabled($user, $category)) {
                return;
            }
        }

        if (! $user instanceof User) {
            return;
        }

        $notification = new SystemNotification($title, $message, $type, $url, $moduleSource);
        $user->notify($notification);

        $notificationId = $notification->id ?? uniqid('broadcast_');

        $this->invalidateUserCache($user);

        $unreadCount = $this->cacheService?->getUnreadCount($user);

        $this->broadcastNotification(
            user: $user,
            notificationId: $notificationId,
            title: $title,
            message: $message,
            type: $type,
            url: $url,
            moduleSource: $moduleSource,
            delivery: self::DELIVERY_BROADCAST,
            action: $action,
            unreadCount: $unreadCount
        );
    }

    /**
     * Send notification with preference check.
     *
     * Checks user preferences before sending if category is provided.
     * Stores to database, invalidates cache, and broadcasts in real-time.
     *
     * @param  Authenticatable  $user  Target user
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string  $type  Notification type
     * @param  string|null  $url  Optional action URL
     * @param  string|null  $moduleSource  Source module identifier
     * @param  string|null  $category  Category for preference check
     * @return void
     */
    private function sendWithPreferenceCheck(
        Authenticatable $user,
        string $title,
        string $message,
        string $type,
        ?string $url,
        ?string $moduleSource,
        ?string $category
    ): void {
        if ($category !== null && $this->preferenceService !== null && $user instanceof User) {
            if (! $this->preferenceService->isEnabled($user, $category)) {
                return;
            }
        }

        $notification = new SystemNotification($title, $message, $type, $url, $moduleSource);

        /** @var User $user */
        $user->notify($notification);

        $notificationId = $notification->id ?? uniqid('notif_');

        $this->invalidateUserCache($user);

        if ($user instanceof User) {
            $unreadCount = $this->cacheService?->getUnreadCount($user);

            $this->broadcastNotification(
                user: $user,
                notificationId: $notificationId,
                title: $title,
                message: $message,
                type: $type,
                url: $url,
                moduleSource: $moduleSource,
                delivery: self::DELIVERY_SILENT,
                unreadCount: $unreadCount
            );
        }
    }

    /**
     * Broadcast notification event for real-time updates.
     *
     * @param  User  $user  Target user
     * @param  string  $notificationId  Notification identifier
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @param  string  $type  Notification type
     * @param  string|null  $url  Optional action URL
     * @param  string|null  $moduleSource  Source module identifier
     * @param  string  $delivery  Delivery mode (silent, flash, trigger, broadcast)
     * @param  string|null  $action  Optional action to trigger on frontend
     * @param  int|null  $unreadCount  Current unread notification count for the user
     * @return void
     */
    private function broadcastNotification(
        User $user,
        string $notificationId,
        string $title,
        string $message,
        string $type,
        ?string $url,
        ?string $moduleSource,
        string $delivery = self::DELIVERY_SILENT,
        ?string $action = null,
        ?int $unreadCount = null
    ): void {
        try {
            event(new \Modules\Signal\Events\NotificationReceived(
                user: $user,
                notificationId: $notificationId,
                title: $title,
                message: $message,
                type: $type,
                url: $url,
                moduleSource: $moduleSource,
                delivery: $delivery,
                action: $action,
                unreadCount: $unreadCount
            ));
        } catch (\Exception $e) {
            if (config('app.debug')) {
                \Illuminate\Support\Facades\Log::debug('Signal broadcast failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Invalidate the user's notification cache.
     *
     * @param  Authenticatable  $user  The user whose cache to invalidate
     * @return void
     */
    private function invalidateUserCache(Authenticatable $user): void
    {
        if ($this->cacheService !== null) {
            $this->cacheService->invalidateUserCache($user);
        }
    }
}

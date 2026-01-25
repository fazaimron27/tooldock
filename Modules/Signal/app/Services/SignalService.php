<?php

/**
 * Signal Service
 *
 * Main service for sending notifications in the Signal module.
 * Provides a clean API for other modules to send notifications
 * without directly instantiating notification classes.
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
     * @param  SignalCacheService|null  $cacheService  Cache service for invalidation
     * @param  SignalPreferenceService|null  $preferenceService  Preference checker service
     */
    public function __construct(
        private ?SignalCacheService $cacheService = null,
        private ?SignalPreferenceService $preferenceService = null
    ) {}

    /**
     * Send an informational notification.
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
     * Send a success notification.
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
     * Send a warning notification.
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
     * Send an alert/error notification.
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
     * Send a notification with custom type.
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
            $this->broadcastNotification($user, $notificationId, $title, $message, $type, $url, $moduleSource);
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
     * @return void
     */
    private function broadcastNotification(
        User $user,
        string $notificationId,
        string $title,
        string $message,
        string $type,
        ?string $url,
        ?string $moduleSource
    ): void {
        try {
            event(new \Modules\Signal\Events\NotificationReceived(
                $user,
                $notificationId,
                $title,
                $message,
                $type,
                $url,
                $moduleSource
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

<?php

/**
 * System Notification
 *
 * Generic notification class for database storage in the Signal module.
 * Provides a flexible notification structure that can be used by any
 * module to send notifications to users.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Class SystemNotification
 *
 * Database-stored notification with support for different types/severities.
 * Broadcasting is handled separately via the NotificationReceived event
 * for real-time delivery.
 *
 * @property string $title The notification title/headline
 * @property string $message The notification body/message content
 * @property string $type The notification type (info, success, warning, error)
 * @property string|null $actionUrl Optional URL for notification action
 * @property string|null $moduleSource Optional source module identifier
 *
 * @see \Modules\Signal\Events\NotificationReceived For real-time broadcasting
 * @see \Modules\Signal\Services\SignalService For sending notifications
 */
class SystemNotification extends Notification
{
    use Queueable;

    /**
     * Valid notification types.
     *
     * @var array<int, string>
     */
    public const TYPES = ['info', 'success', 'warning', 'error'];

    /**
     * Create a new notification instance.
     *
     * Validates the notification type and falls back to 'info' if invalid.
     *
     * @param  string  $title  Display title for the notification
     * @param  string  $message  Detailed message content
     * @param  string  $type  Notification type (info, success, warning, error)
     * @param  string|null  $actionUrl  Optional URL for click-through action
     * @param  string|null  $moduleSource  Optional identifier of the originating module
     * @return void
     */
    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'info',
        public ?string $actionUrl = null,
        public ?string $moduleSource = null,
    ) {
        if (! in_array($this->type, self::TYPES)) {
            $this->type = 'info';
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * Currently only database storage is used. Real-time broadcasting
     * is handled via a separate event for better control and flexibility.
     *
     * @param  object  $notifiable  The notifiable entity (typically User)
     * @return array<int, string> Array of delivery channel names
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation for database storage.
     *
     * Structures the notification data for storage in the notifications table.
     * This data will be JSON-encoded in the 'data' column.
     *
     * @param  object  $notifiable  The notifiable entity (typically User)
     * @return array<string, mixed> Notification data for database storage
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'action_url' => $this->actionUrl,
            'module_source' => $this->moduleSource,
        ];
    }
}

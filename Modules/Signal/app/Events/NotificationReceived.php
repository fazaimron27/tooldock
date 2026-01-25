<?php

/**
 * NotificationReceived Event
 *
 * Broadcast event for real-time notification delivery via WebSocket.
 * This event is dispatched when a notification is sent to a user and
 * enables instant updates in the frontend via Laravel Echo/Reverb.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Models\User;

/**
 * Class NotificationReceived
 *
 * Broadcast event that fires immediately (not queued) when a notification
 * is sent to a user. Uses private channels for secure user-specific delivery.
 *
 * @property User $user The recipient user model
 * @property string $notificationId Unique identifier for the notification
 * @property string $title Notification title/headline
 * @property string $message Notification body/message content
 * @property string $type Notification type (info, success, warning, error)
 * @property string|null $url Optional action URL for the notification
 * @property string|null $moduleSource Optional source module identifier
 *
 * @see \Modules\Signal\Services\SignalService::broadcastNotification()
 */
class NotificationReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * Initializes the broadcast event with notification data for real-time delivery.
     *
     * @param  User  $user  The target user who will receive the notification
     * @param  string  $notificationId  Unique identifier for this notification
     * @param  string  $title  Display title for the notification
     * @param  string  $message  Detailed message content
     * @param  string  $type  Notification severity type (info, success, warning, error)
     * @param  string|null  $url  Optional URL for notification action/redirect
     * @param  string|null  $moduleSource  Optional identifier of the originating module
     * @return void
     */
    public function __construct(
        public User $user,
        public string $notificationId,
        public string $title,
        public string $message,
        public string $type,
        public ?string $url = null,
        public ?string $moduleSource = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * Uses a private channel scoped to the specific user to ensure
     * notifications are only delivered to the intended recipient.
     * Channel naming follows Laravel conventions for user notifications.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel> Array of broadcast channels
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->user->id),
        ];
    }

    /**
     * Get the data payload to broadcast with the event.
     *
     * Structures the notification data for frontend consumption,
     * including timestamp for proper ordering and display.
     *
     * @return array<string, mixed> Associative array of notification data
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificationId,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'url' => $this->url,
            'module_source' => $this->moduleSource,
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the event's broadcast name.
     *
     * Defines a custom event name for Laravel Echo listeners.
     * Frontend can listen for 'notification.received' events.
     *
     * @return string The broadcast event name
     */
    public function broadcastAs(): string
    {
        return 'notification.received';
    }
}

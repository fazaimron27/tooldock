<?php

/**
 * WebhookSent Event
 *
 * Broadcast event fired when an outbound webhook delivery completes.
 * Broadcasts immediately on the outbound webhook owner's private channel.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Hook\Models\HookOutboundDelivery;

/**
 * Class WebhookSent
 *
 * Broadcasts delivery data to the user's private channel for real-time UI updates.
 */
class WebhookSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public HookOutboundDelivery $delivery,
        public string $userId
    ) {}

    /**
     * @return array<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'hook.webhook.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'outboundDelivery' => $this->delivery->toArray(),
        ];
    }
}

<?php

/**
 * WebhookReceived Event
 *
 * Broadcast event fired when an inbound webhook is received.
 * Broadcasts immediately on the inbound endpoint owner's private channel.
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
use Modules\Hook\Models\HookInboundRequest;

/**
 * Class WebhookReceived
 *
 * Broadcasts received webhook data to the user's private channel
 * for real-time UI updates.
 */
class WebhookReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public HookInboundRequest $inboundRequest,
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
        return 'hook.webhook.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'inboundRequest' => $this->inboundRequest->toArray(),
        ];
    }
}

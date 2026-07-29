<?php

/**
 * WebhookReceivedHandler
 *
 * Signal handler that fires a flash notification when an inbound
 * webhook endpoint receives a new request. Follows the SignalHandlerInterface
 * contract: returns a notification array, the registry drives delivery.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Services\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Hook\Models\HookInboundRequest;

/**
 * Class WebhookReceivedHandler
 */
class WebhookReceivedHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['hook.webhook.received'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Hook';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'WebhookReceivedHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        if (! is_array($data) || ! isset($data['request']) || ! ($data['request'] instanceof HookInboundRequest)) {
            return false;
        }

        $inbound = $data['request']->inbound;

        // Bot-owned inbounds are handled by the Bot module — suppress the Hook notification.
        // Static cache: Schema::hasTable hits the DB, no need to repeat it every request.
        static $botTableExists = null;
        $botTableExists ??= Schema::hasTable('bot_platforms');

        if (
            $inbound
            && $botTableExists
            && DB::table('bot_platforms')->where('hook_inbound_slug', $inbound->slug)->exists()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Return a flash notification when an inbound webhook is received.
     *
     * {@inheritdoc}
     */
    public function handle(mixed $data): ?array
    {
        /** @var HookInboundRequest $request */
        $request = $data['request'];
        $inbound = $request->inbound;

        if (! $inbound) {
            return null;
        }

        $source = $request->source_ip ?? 'unknown';

        return [
            'type' => 'info',
            'title' => 'Webhook received',
            'message' => "{$inbound->name} ← {$source}",
            'url' => route('hook.inbound.show', $inbound->id),
            'category' => 'hook_inbound',
            'delivery' => 'flash',
        ];
    }
}

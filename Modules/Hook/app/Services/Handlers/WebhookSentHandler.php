<?php

/**
 * WebhookSentHandler
 *
 * Signal handler that fires a flash notification when an outbound
 * webhook delivery completes (success or failure). Follows the
 * SignalHandlerInterface contract: returns a notification array,
 * the registry drives delivery.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Services\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Modules\Hook\Models\HookOutboundDelivery;

/**
 * Class WebhookSentHandler
 */
class WebhookSentHandler implements SignalHandlerInterface
{
    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['hook.webhook.sent'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Hook';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'WebhookSentHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return is_array($data)
            && isset($data['delivery'])
            && $data['delivery'] instanceof HookOutboundDelivery;
    }

    /**
     * Return a flash notification when an outbound delivery completes.
     *
     * {@inheritdoc}
     */
    public function handle(mixed $data): ?array
    {
        // Observer-triggered deliveries are silent — no flash notification.
        if (! empty($data['silent'])) {
            return null;
        }

        /** @var HookOutboundDelivery $delivery */
        $delivery = $data['delivery'];
        $outbound = $delivery->outbound;

        if (! $outbound) {
            return null;
        }

        $ok = $delivery->response_status !== null
            && $delivery->response_status >= 200
            && $delivery->response_status < 300;
        $status = $delivery->response_status ?? '—';
        $ms = $delivery->duration_ms ? " · {$delivery->duration_ms}ms" : '';
        $err = (! $ok && $delivery->error_message) ? " — {$delivery->error_message}" : '';

        return [
            'type' => $ok ? 'success' : 'error',
            'title' => $ok ? 'Webhook delivered' : 'Webhook failed',
            'message' => "{$outbound->name} → {$status}{$ms}{$err}",
            'category' => 'hook_outbound',
            'delivery' => 'flash',
        ];
    }
}

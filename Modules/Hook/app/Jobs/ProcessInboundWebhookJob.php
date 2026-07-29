<?php

/**
 * ProcessInboundWebhookJob
 *
 * Broadcasts a WebhookReceived event to the inbound endpoint owner's
 * private channel for real-time UI updates.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Jobs;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Hook\Events\WebhookReceived;
use Modules\Hook\Models\HookInboundRequest;
use Throwable;

/**
 * Class ProcessInboundWebhookJob
 *
 * Queued job that broadcasts a received webhook event to the user's private channel.
 * Uses exponential backoff for resilience.
 */
class ProcessInboundWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public HookInboundRequest $inboundRequest,
        public string $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SignalHandlerRegistry $signalRegistry): void
    {
        event(new WebhookReceived($this->inboundRequest, $this->userId));

        try {
            $signalRegistry->dispatch('hook.webhook.received', [
                'user' => User::find($this->userId),
                'request' => $this->inboundRequest,
            ]);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::debug('ProcessInboundWebhookJob: Signal dispatch failed: '.$e->getMessage());
            }
        }
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    /**
     * @param  Throwable|null  $exception
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('ProcessInboundWebhookJob: Failed to broadcast received webhook', [
            'inbound_request_id' => $this->inboundRequest->id,
            'user_id' => $this->userId,
            'error' => $exception?->getMessage(),
        ]);
    }
}

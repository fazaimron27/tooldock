<?php

/**
 * SendOutboundWebhookJob
 *
 * Executes an outbound HTTP request to the configured target URL,
 * measures duration, stores the delivery result, and broadcasts the outcome.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Jobs;

use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Hook\Enums\HookOutboundProvider;
use Modules\Hook\Events\WebhookSent;
use Modules\Hook\Models\HookOutbound;
use Modules\Hook\Models\HookOutboundDelivery;
use Modules\Hook\Rules\PublicHttpUrl;
use Throwable;

/**
 * Class SendOutboundWebhookJob
 *
 * Single-attempt job that sends an HTTP request to the target URL
 * and stores the full response details for inspection.
 */
class SendOutboundWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Single attempt to avoid duplicate delivery records.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public HookOutbound $outbound,
        public string $userId,
        public ?array $headersOverride = null,
        public ?array $payloadOverride = null,
        public bool $silent = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SignalHandlerRegistry $signalRegistry, PublicHttpUrl $publicHttpUrl): void
    {
        $outbound = $this->outbound;
        $startTime = microtime(true);
        $delivery = null;

        if (! $outbound->is_active) {
            Log::info('SendOutboundWebhookJob: skipped inactive outbound.', ['id' => $outbound->id]);

            return;
        }

        try {
            $provider = $outbound->provider instanceof HookOutboundProvider
                ? $outbound->provider
                : HookOutboundProvider::Generic;

            $url = $provider === HookOutboundProvider::Generic
                ? $outbound->target_url
                : $provider->resolveUrl($outbound->provider_config ?? []);

            if (! $url) {
                throw new \RuntimeException('No URL could be resolved for outbound webhook.');
            }

            if ($provider === HookOutboundProvider::Generic && ! $publicHttpUrl->isAllowed($url)) {
                throw new \RuntimeException('Generic outbound webhook URL is not a public HTTP or HTTPS address.');
            }

            if ($provider !== HookOutboundProvider::Generic && ! $provider->isValidUrl($url)) {
                throw new \RuntimeException("Outbound webhook URL is invalid for the {$provider->label()} provider.");
            }

            $headers = array_merge(
                $provider->defaultHeaders(),
                $outbound->headers ?? [],
                $this->headersOverride ?? [],
            );

            $payload = $this->payloadOverride ?? $outbound->payload_template;

            $config = $outbound->provider_config ?? [];
            if ($provider !== HookOutboundProvider::Generic && ! empty($config) && is_array($payload)) {
                array_walk_recursive($payload, function (mixed &$value) use ($config): void {
                    if (is_string($value)) {
                        $value = preg_replace_callback(
                            '/\{\{(\w+)\}\}/',
                            fn (array $m) => array_key_exists($m[1], $config) ? (string) $config[$m[1]] : $m[0],
                            $value,
                        );
                    }
                });
            }

            $pendingRequest = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders($headers)
                ->withoutRedirecting();

            /** @var Response $response */
            $response = $pendingRequest->send(
                $outbound->method,
                $url,
                ['json' => $payload]
            );

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $responseHeaders = collect($response->headers())->map(function ($values) {
                return count($values) === 1 ? $values[0] : $values;
            })->toArray();

            $delivery = HookOutboundDelivery::create([
                'outbound_id' => $outbound->id,
                'response_status' => $response->status(),
                'response_headers' => $responseHeaders,
                'response_body' => $response->body(),
                'duration_ms' => $durationMs,
                'error_message' => null,
            ]);
        } catch (ConnectionException $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $delivery = HookOutboundDelivery::create([
                'outbound_id' => $outbound->id,
                'response_status' => null,
                'response_headers' => null,
                'response_body' => null,
                'duration_ms' => $durationMs,
                'error_message' => $e->getMessage(),
            ]);
        }

        if ($delivery) {
            event(new WebhookSent($delivery, $this->userId));

            try {
                $signalRegistry->dispatch('hook.webhook.sent', [
                    'user' => User::find($this->userId),
                    'delivery' => $delivery,
                    'silent' => $this->silent,
                ]);
            } catch (\Exception $e) {
                if (config('app.debug')) {
                    Log::debug('SendOutboundWebhookJob: Signal dispatch failed: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  Throwable|null  $exception
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('SendOutboundWebhookJob: Failed to send outbound webhook', [
            'outbound_id' => $this->outbound->id,
            'target_url' => $this->outbound->target_url,
            'user_id' => $this->userId,
            'error' => $exception?->getMessage(),
        ]);
    }
}

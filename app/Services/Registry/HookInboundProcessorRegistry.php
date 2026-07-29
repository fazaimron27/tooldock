<?php

/**
 * HookInboundProcessorRegistry
 *
 * Collects processors registered by modules and provides a clean API
 * for HookInboundController to intercept inbound requests without
 * knowing anything about the iteration or matching logic.
 *
 * Modules register processors in their ServiceProvider::boot():
 *   app(HookInboundProcessorRegistry::class)->register(new MyProcessor());
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Hook\Models\HookInbound;

class HookInboundProcessorRegistry
{
    /** @var list<HookInboundProcessorInterface> */
    private array $processors = [];

    /** @var HookInboundProcessorInterface|null The processor matched for the current request. */
    private ?HookInboundProcessorInterface $matched = null;

    /**
     * Register a processor.
     * Called by modules in their ServiceProvider::boot().
     */
    public function register(HookInboundProcessorInterface $processor): void
    {
        $this->processors[] = $processor;
    }

    /**
     * Run handles() -> verify() -> early() for the first matching processor.
     *
     * Returns a Response to short-circuit, or null to proceed with store + queue.
     * Stores the matched processor internally so respond() can be called after storing.
     */
    public function intercept(Request $request, HookInbound $inbound): ?JsonResponse
    {
        $this->matched = null;

        foreach ($this->processors as $processor) {
            if (! $processor->handles($request, $inbound)) {
                continue;
            }

            if ($deny = $processor->verify($request, $inbound)) {
                return $deny;
            }

            if ($early = $processor->early($request, $inbound)) {
                return $early;
            }

            $this->matched = $processor;
            break;
        }

        return null;
    }

    /**
     * Allow the matched processor to override the success response after storing.
     * Must be called after intercept(). Returns null if no processor matched or
     * the matched processor has no custom response.
     */
    public function respond(Request $request, HookInbound $inbound): ?JsonResponse
    {
        return $this->matched?->respond($request, $inbound);
    }
}

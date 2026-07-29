<?php

/**
 * Hook Event Registry Interface
 *
 * Stable contract for registering hookable model lifecycle triggers
 * with the Hook module's event registry. Lives at root so any module
 * can depend on it safely, even when Hook itself is optional/uninstalled.
 *
 * Usage in a module's service provider:
 *
 *   if (app()->bound(HookEventRegistryInterface::class)) {
 *       (new MyModuleHookRegistrar)->register(
 *           app(HookEventRegistryInterface::class)
 *       );
 *   }
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

use Modules\Hook\Services\HookEventRegistry;

/**
 * Interface HookEventRegistryInterface
 *
 * Mirrors the pattern of SignalHandlerInterface — a root-level contract
 * that modules can depend on without coupling to the Hook module itself.
 *
 * @see HookEventRegistry For the concrete implementation
 */
interface HookEventRegistryInterface
{
    /**
     * Register a hookable model lifecycle trigger.
     *
     * @param  string  $key  Unique trigger key, e.g. 'treasury.transaction_created'
     * @param  string  $label  Human-readable label shown in the outbound trigger dropdown
     * @param  class-string  $modelClass  The Eloquent model class to observe
     * @param  string  $on  Eloquent lifecycle event: 'created'|'updated'|'deleted'
     * @param  list<string>  $payloadSchema  Attribute names exposed in the webhook payload
     * @param  callable|null  $formatter  Optional formatter to shape the payload data
     */
    public function register(
        string $key,
        string $label,
        string $modelClass,
        string $on = 'created',
        array $payloadSchema = [],
        ?callable $formatter = null,
    ): void;
}

<?php

/**
 * Hook Signal Registrar
 *
 * Registers all Hook module signal handlers with the central
 * SignalHandlerRegistry, following the pattern established by
 * TreasurySignalRegistrar and AuditLogSignalRegistrar.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Services;

use App\Services\Registry\SignalHandlerInterface;
use App\Services\Registry\SignalHandlerRegistry;
use Modules\Hook\Services\Handlers\WebhookReceivedHandler;
use Modules\Hook\Services\Handlers\WebhookSentHandler;

/**
 * Class HookSignalRegistrar
 *
 * Registers all Hook module signal handlers with the central registry.
 * Called from HookServiceProvider during application boot.
 */
class HookSignalRegistrar
{
    private const MODULE_NAME = 'Hook';

    /**
     * Signal handler class names.
     *
     * @var array<class-string<SignalHandlerInterface>>
     */
    private const HANDLERS = [
        WebhookReceivedHandler::class,
        WebhookSentHandler::class,
    ];

    /**
     * Register all Hook signal handlers with the registry.
     *
     * @param  SignalHandlerRegistry  $registry
     * @return void
     */
    public function register(SignalHandlerRegistry $registry): void
    {
        foreach (self::HANDLERS as $handlerClass) {
            $registry->register(self::MODULE_NAME, $handlerClass);
        }
    }
}

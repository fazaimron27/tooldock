<?php

/**
 * Bot Signal Registrar
 *
 * Registers Bot module signal handlers with the central SignalHandlerRegistry.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Bot\Services;

use App\Services\Registry\SignalHandlerRegistry;
use Modules\Bot\Handlers\InboundWebhookHandler;

class BotSignalRegistrar
{
    private const MODULE_NAME = 'Bot';

    /**
     * Signal handler class names.
     * Add concrete handlers here as they are implemented.
     *
     * @var array<class-string>
     */
    private const HANDLERS = [
        InboundWebhookHandler::class,
    ];

    public function register(SignalHandlerRegistry $registry): void
    {
        foreach (self::HANDLERS as $handlerClass) {
            $registry->register(self::MODULE_NAME, $handlerClass);
        }
    }
}

<?php

namespace Modules\Settings\Services;

use App\Services\Registry\SignalHandlerInterface;
use App\Services\Registry\SignalHandlerRegistry;
use Modules\Settings\Services\Handlers\SettingsChangedHandler;

/**
 * Settings Signal Registrar
 *
 * Registers all Settings module signal handlers with the central SignalHandlerRegistry.
 */
class SettingsSignalRegistrar
{
    private const MODULE_NAME = 'Settings';

    /**
     * Signal handler class names.
     *
     * @var array<class-string<SignalHandlerInterface>>
     */
    private const HANDLERS = [
        SettingsChangedHandler::class,
    ];

    public function register(SignalHandlerRegistry $registry): void
    {
        foreach (self::HANDLERS as $handlerClass) {
            $registry->register(self::MODULE_NAME, $handlerClass);
        }
    }
}

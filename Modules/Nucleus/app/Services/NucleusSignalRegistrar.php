<?php

/**
 * Nucleus Signal Registrar.
 *
 * Registers signal handlers for the Nucleus module.
 * Currently a no-op placeholder.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Services;

use App\Services\Registry\SignalHandlerRegistry;

/**
 * Class NucleusSignalRegistrar
 *
 * Registers all Nucleus module signal handlers with the central SignalHandlerRegistry.
 */
class NucleusSignalRegistrar
{
    private const MODULE_NAME = 'Nucleus';

    /**
     * Signal handler class names.
     *
     * @var array<class-string>
     */
    private const HANDLERS = [
        // Add Nucleus signal handlers here as the module grows.
    ];

    /**
     * Register all signal handlers with the central registry.
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

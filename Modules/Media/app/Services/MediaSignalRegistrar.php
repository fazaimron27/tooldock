<?php

/**
 * Media Signal Registrar.
 *
 * Registers signal handlers for media-related events.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Media\Services;

use App\Services\Registry\SignalHandlerInterface;
use App\Services\Registry\SignalHandlerRegistry;
use Modules\Media\Services\Handlers\MediaUploadFailedHandler;

/**
 * Media Signal Registrar
 *
 * Registers all Media module signal handlers with the central SignalHandlerRegistry.
 */
class MediaSignalRegistrar
{
    private const MODULE_NAME = 'Media';

    /**
     * Signal handler class names.
     *
     * @var array<class-string<SignalHandlerInterface>>
     */
    private const HANDLERS = [
        MediaUploadFailedHandler::class,
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

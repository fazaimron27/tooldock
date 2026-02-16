<?php

/**
 * Audit Log Signal Registrar.
 *
 * Registers signal handlers for the AuditLog module, enabling
 * notifications for job failures and cleanup completion events.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\AuditLog\Services;

use App\Services\Registry\SignalHandlerInterface;
use App\Services\Registry\SignalHandlerRegistry;
use Modules\AuditLog\Services\Handlers\AuditLogCleanupCompletedHandler;
use Modules\AuditLog\Services\Handlers\AuditLogJobFailedHandler;

/**
 * AuditLog Signal Registrar
 *
 * Registers all AuditLog module signal handlers with the central SignalHandlerRegistry.
 */
class AuditLogSignalRegistrar
{
    private const MODULE_NAME = 'AuditLog';

    /**
     * Signal handler class names.
     *
     * @var array<class-string<SignalHandlerInterface>>
     */
    private const HANDLERS = [
        AuditLogJobFailedHandler::class,
        AuditLogCleanupCompletedHandler::class,
    ];

    /**
     * Register all signal handlers with the central registry.
     *
     * @param  SignalHandlerRegistry  $registry  The signal handler registry to register into
     * @return void
     */
    public function register(SignalHandlerRegistry $registry): void
    {
        foreach (self::HANDLERS as $handlerClass) {
            $registry->register(self::MODULE_NAME, $handlerClass);
        }
    }
}

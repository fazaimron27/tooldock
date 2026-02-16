<?php

namespace Modules\Vault\Services;

use App\Services\Registry\SignalHandlerInterface;
use App\Services\Registry\SignalHandlerRegistry;
use Modules\Vault\Services\Lock\Handlers\VaultAutoLockedHandler;
use Modules\Vault\Services\Lock\Handlers\VaultLockedHandler;
use Modules\Vault\Services\Lock\Handlers\VaultPinChangedHandler;
use Modules\Vault\Services\Lock\Handlers\VaultUnlockedHandler;

/**
 * Vault Signal Registrar
 *
 * Registers all Vault module signal handlers with the central SignalHandlerRegistry.
 * Called from VaultServiceProvider during application boot.
 */
class VaultSignalRegistrar
{
    private const MODULE_NAME = 'Vault';

    /**
     * Signal handler class names organized by category.
     *
     * @var array<string, array<class-string<SignalHandlerInterface>>>
     */
    private const HANDLERS = [
        'lock' => [
            VaultUnlockedHandler::class,
            VaultLockedHandler::class,
            VaultAutoLockedHandler::class,
            VaultPinChangedHandler::class,
        ],
    ];

    /**
     * Register all Vault signal handler classes with the registry.
     */
    public function register(SignalHandlerRegistry $registry): void
    {
        foreach (self::HANDLERS as $handlers) {
            foreach ($handlers as $handlerClass) {
                $registry->register(self::MODULE_NAME, $handlerClass);
            }
        }
    }

    /**
     * Get all handler classes by category.
     *
     * @return array<string, array<class-string<SignalHandlerInterface>>>
     */
    public static function getHandlersByCategory(): array
    {
        return self::HANDLERS;
    }

    /**
     * Get all handler class names as a flat array.
     *
     * @return array<class-string<SignalHandlerInterface>>
     */
    public static function getAllHandlerClasses(): array
    {
        return array_merge(...array_values(self::HANDLERS));
    }

    /**
     * Get the count of registered handlers.
     */
    public static function getHandlerCount(): int
    {
        return count(self::getAllHandlerClasses());
    }
}

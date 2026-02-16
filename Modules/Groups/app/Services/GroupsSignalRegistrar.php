<?php

namespace Modules\Groups\Services;

use App\Services\Registry\SignalHandlerInterface;
use App\Services\Registry\SignalHandlerRegistry;
use Modules\Groups\Services\Group\Handlers\GroupPermissionsSyncedHandler;
use Modules\Groups\Services\Group\Handlers\GroupRolesSyncedHandler;
use Modules\Groups\Services\Member\Handlers\MemberAddedHandler;
use Modules\Groups\Services\Member\Handlers\MemberRemovedHandler;
use Modules\Groups\Services\Member\Handlers\MemberTransferredHandler;

/**
 * Groups Signal Registrar
 *
 * Registers all Groups module signal handlers with the central SignalHandlerRegistry.
 * Called from GroupsServiceProvider during application boot.
 */
class GroupsSignalRegistrar
{
    private const MODULE_NAME = 'Groups';

    /**
     * Signal handler class names organized by category.
     *
     * @var array<string, array<class-string<SignalHandlerInterface>>>
     */
    private const HANDLERS = [
        'member' => [
            MemberAddedHandler::class,
            MemberRemovedHandler::class,
            MemberTransferredHandler::class,
        ],
        'group' => [
            GroupPermissionsSyncedHandler::class,
            GroupRolesSyncedHandler::class,
        ],
    ];

    /**
     * Register all Groups signal handler classes with the registry.
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

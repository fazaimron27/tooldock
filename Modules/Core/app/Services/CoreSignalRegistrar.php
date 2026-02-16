<?php

/**
 * Core Signal Registrar.
 *
 * Registers signal handlers for the Core module including
 * authentication, user, and role event handlers.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Services;

use App\Services\Registry\SignalHandlerInterface;
use App\Services\Registry\SignalHandlerRegistry;
use Modules\Core\Services\Auth\Handlers\EmailVerifiedHandler;
use Modules\Core\Services\Auth\Handlers\NewUserAdminHandler;
use Modules\Core\Services\Auth\Handlers\PasswordChangedHandler;
use Modules\Core\Services\Auth\Handlers\PasswordResetHandler;
use Modules\Core\Services\Auth\Handlers\UserLockoutHandler;
use Modules\Core\Services\Auth\Handlers\UserLoginHandler;
use Modules\Core\Services\Auth\Handlers\UserRegisteredHandler;
use Modules\Core\Services\Role\Handlers\RolePermissionsSyncedHandler;
use Modules\Core\Services\User\Handlers\EmailChangedHandler;
use Modules\Core\Services\User\Handlers\UserRoleChangedHandler;

/**
 * Core Signal Registrar
 *
 * Registers all Core module signal handlers with the central SignalHandlerRegistry.
 * Called from CoreServiceProvider during application boot.
 */
class CoreSignalRegistrar
{
    private const MODULE_NAME = 'Core';

    /**
     * Signal handler class names organized by category.
     *
     * @var array<string, array<class-string<SignalHandlerInterface>>>
     */
    private const HANDLERS = [
        'auth' => [
            UserLoginHandler::class,
            UserLockoutHandler::class,
            PasswordChangedHandler::class,
            PasswordResetHandler::class,
            EmailVerifiedHandler::class,
            UserRegisteredHandler::class,
            NewUserAdminHandler::class,
        ],
        'user' => [
            UserRoleChangedHandler::class,
            EmailChangedHandler::class,
        ],
        'role' => [
            RolePermissionsSyncedHandler::class,
        ],
    ];

    /**
     * Register all Core signal handler classes with the registry.
     *
     * @param  SignalHandlerRegistry  $registry  The signal handler registry
     * @return void
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
     *
     * @return int The total number of signal handlers
     */
    public static function getHandlerCount(): int
    {
        return count(self::getAllHandlerClasses());
    }
}

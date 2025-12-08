<?php

namespace App\Services\Registry;

/**
 * Registry for managing application middleware registration.
 *
 * Allows modules to register their middleware during service provider boot,
 * which are then automatically collected and applied in bootstrap/app.php.
 *
 * Uses a static array internally to allow access before service providers boot,
 * while maintaining singleton pattern for consistency with other registries.
 */
class MiddlewareRegistry
{
    /**
     * Static array to store middleware classes for early access.
     *
     * Format: ['module' => ['middleware1', 'middleware2']]
     *
     * @var array<string, array<string>>
     */
    private static array $middleware = [];

    /**
     * Track registered middleware by class name to prevent duplicates.
     *
     * Format: ['MiddlewareClass' => 'ModuleName']
     *
     * @var array<string, string>
     */
    private static array $registeredMiddleware = [];

    /**
     * Register middleware for a module.
     *
     * Validates that the middleware class is unique across all modules.
     * If a duplicate middleware is detected from a different module, a RuntimeException is thrown.
     *
     * @param  string  $module  Module name (e.g., 'Vault', 'Blog')
     * @param  string  $middlewareClass  Fully qualified middleware class name
     *
     * @throws \RuntimeException When a duplicate middleware is registered by a different module
     */
    public function register(string $module, string $middlewareClass): void
    {
        if (isset(self::$registeredMiddleware[$middlewareClass])) {
            if (self::$registeredMiddleware[$middlewareClass] !== $module) {
                throw new \RuntimeException(
                    "Middleware '{$middlewareClass}' is already registered by module '".self::$registeredMiddleware[$middlewareClass]."'. ".
                        "Module '{$module}' cannot register a duplicate middleware."
                );
            }

            return;
        }

        self::$registeredMiddleware[$middlewareClass] = $module;

        if (! isset(self::$middleware[$module])) {
            self::$middleware[$module] = [];
        }

        self::$middleware[$module][] = $middlewareClass;
    }

    /**
     * Get all registered middleware classes.
     *
     * Returns a flat array of all middleware classes from all modules.
     *
     * @return array<string>
     */
    public function getAll(): array
    {
        return self::getAllStatic();
    }

    /**
     * Get all registered middleware classes (static method for early access).
     *
     * Returns a flat array of all middleware classes from all modules.
     * Can be called before service providers boot.
     *
     * @return array<string>
     */
    public static function getAllStatic(): array
    {
        if (empty(self::$middleware)) {
            return [];
        }

        return array_values(array_unique(array_merge(...array_values(self::$middleware))));
    }

    /**
     * Get middleware for a specific module.
     *
     * @param  string  $module  Module name
     * @return array<string>
     */
    public function getByModule(string $module): array
    {
        return self::$middleware[$module] ?? [];
    }

    /**
     * Clear all registered middleware (useful for testing).
     */
    public function clear(): void
    {
        self::$middleware = [];
        self::$registeredMiddleware = [];
    }
}

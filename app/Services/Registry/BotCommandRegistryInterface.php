<?php

/**
 * Bot Command Registry Interface
 *
 * Stable contract for registering bot commands with the Bot module's
 * command registry. Lives at root so any module can depend on it safely,
 * even when Bot itself is optional/uninstalled.
 *
 * Usage in a module's service provider:
 *
 *   if (app()->bound(BotCommandRegistryInterface::class)) {
 *       (new MyModuleBotRegistrar)->register(
 *           app(BotCommandRegistryInterface::class)
 *       );
 *   }
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

use Modules\Bot\Services\BotCommandRegistry;

/**
 * Interface BotCommandRegistryInterface
 *
 * @see BotCommandRegistry For the concrete implementation
 */
interface BotCommandRegistryInterface
{
    /**
     * Register a bot command.
     *
     * @param  string  $key  Unique command key, e.g. 'treasury.balance'
     * @param  string  $label  Human-readable label shown in UI
     * @param  callable  $handler  callable(BotContext): BotMessage
     * @param  array  $schema  Optional list of argument names the handler expects
     */
    public function register(string $key, string $label, callable $handler, array $schema = []): void;

    /**
     * @return array<string, array{label: string, handler: callable, schema: list<string>}>
     */
    public function all(): array;

    /**
     * Resolve a single command definition by key.
     *
     * @return array{label: string, handler: callable, schema: list<string>}|null
     */
    public function resolve(string $key): ?array;
}

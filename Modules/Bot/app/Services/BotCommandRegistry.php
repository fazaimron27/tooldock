<?php

namespace Modules\Bot\Services;

use App\Services\Registry\BotCommandRegistryInterface;

/**
 * BotCommandRegistry
 *
 * Singleton registry. Other modules register their commands here.
 * Bound under both this concrete class and BotCommandRegistryInterface.
 */
class BotCommandRegistry implements BotCommandRegistryInterface
{
    /** @var array<string, array{label: string, handler: callable, schema: list<string>}> */
    private array $commands = [];

    public function register(string $key, string $label, callable $handler, array $schema = []): void
    {
        $this->commands[$key] = compact('label', 'handler', 'schema');
    }

    public function all(): array
    {
        return $this->commands;
    }

    public function resolve(string $key): ?array
    {
        return $this->commands[$key] ?? null;
    }
}

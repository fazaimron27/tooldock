<?php

/**
 * Command Registry
 *
 * Registry for Command Palette commands with permission-based
 * visibility and module-scoped registration. Provides grouped
 * and sorted command retrieval for the frontend.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/**
 * Class CommandRegistry
 *
 * Manages command palette entries registered by modules during
 * service provider boot. Commands are grouped by display group,
 * filtered by user permissions, and sorted by a predefined group
 * order. Supports parent grouping for nested command organization.
 *
 * @see \Illuminate\Support\Facades\Gate For permission-based filtering
 */
class CommandRegistry
{
    /**
     * @var array<string, array<int, array{group: string, parent: ?string, label: string, route: ?string, url: ?string, action: ?string, icon: string, permission: ?string, keywords: array, newTab: bool, description: ?string, order: int}>>
     */
    private array $commands = [];

    /**
     * Register a single command for a module.
     *
     * Adds a command entry to the registry under the given module.
     * Commands can specify a route, URL, or action for navigation,
     * along with permission requirements and search keywords.
     *
     * @param  string  $module  Module name registering the command (e.g., 'Core', 'Treasury')
     * @param  string  $group  Display group name (e.g., 'Quick Actions', 'User Management')
     * @param  array  $command  Command definition with keys: label (required), route, url, action, icon, permission, keywords, newTab, description, order, parent
     * @return void
     */
    public function register(string $module, string $group, array $command): void
    {
        if (! isset($this->commands[$module])) {
            $this->commands[$module] = [];
        }

        $this->commands[$module][] = [
            'group' => $group,
            'parent' => $command['parent'] ?? null,
            'label' => $command['label'],
            'route' => $command['route'] ?? null,
            'url' => $command['url'] ?? null,
            'action' => $command['action'] ?? null,
            'icon' => $command['icon'] ?? 'file-text',
            'permission' => $command['permission'] ?? null,
            'keywords' => $command['keywords'] ?? [],
            'newTab' => $command['newTab'] ?? false,
            'description' => $command['description'] ?? null,
            'order' => $command['order'] ?? 0,
        ];
    }

    /**
     * Register multiple commands for a module in a single call.
     *
     * Convenience method that iterates over an array of command
     * definitions, delegating each to the `register()` method.
     *
     * @param  string  $module  Module name registering the commands
     * @param  string  $group  Display group name for all commands
     * @param  array  $commands  Array of command definitions
     * @return void
     */
    public function registerMany(string $module, string $group, array $commands): void
    {
        foreach ($commands as $command) {
            $this->register($module, $group, $command);
        }
    }

    /**
     * Get all commands grouped and filtered for a specific user.
     *
     * Filters commands by user permissions using Gate checks, groups
     * them by display group (or parent group if specified), sorts
     * within each group by order, and sorts groups by a predefined
     * priority order. Commands requiring permissions are excluded
     * for unauthenticated users.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user  The authenticated user, or null for guest
     * @return array<string, array<int, array{label: string, route: ?string, url: ?string, action: ?string, icon: string, keywords: array, newTab: bool, description: ?string}>> Commands grouped by display group
     */
    public function getCommands(?Authenticatable $user = null): array
    {
        $grouped = [];

        foreach ($this->commands as $module => $moduleCommands) {
            foreach ($moduleCommands as $command) {
                if ($command['permission'] && $user) {
                    if (! Gate::forUser($user)->allows($command['permission'])) {
                        continue;
                    }
                }

                if ($command['permission'] && ! $user) {
                    continue;
                }

                $displayGroup = $command['parent'] ?? $command['group'];

                if (! isset($grouped[$displayGroup])) {
                    $grouped[$displayGroup] = [];
                }

                $grouped[$displayGroup][] = [
                    'label' => $command['label'],
                    'route' => $command['route'],
                    'url' => $command['url'],
                    'action' => $command['action'],
                    'icon' => $command['icon'],
                    'keywords' => $command['keywords'],
                    'newTab' => $command['newTab'],
                    'description' => $command['description'],
                    'order' => $command['order'],
                ];
            }
        }

        foreach ($grouped as $group => $commands) {
            usort($commands, fn ($a, $b) => $a['order'] <=> $b['order']);

            $grouped[$group] = array_map(function ($cmd) {
                unset($cmd['order']);

                return $cmd;
            }, $commands);
        }

        $grouped = array_filter($grouped, fn ($items) => ! empty($items));

        $groupOrder = [
            'Quick Actions' => 0,
            'User Management' => 1,
            'Life OS' => 3,
            'Utilities' => 4,
            'Master Data' => 5,
            'Platform' => 6,
            'System' => 7,
        ];

        uksort($grouped, function ($groupA, $groupB) use ($groupOrder) {
            $orderA = $groupOrder[$groupA] ?? 999;
            $orderB = $groupOrder[$groupB] ?? 999;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcasecmp($groupA, $groupB);
        });

        return $grouped;
    }

    /**
     * Get all registered commands without filtering.
     *
     * Returns the raw command registry indexed by module name.
     * Primarily used for debugging and introspection.
     *
     * @return array<string, array> All registered commands by module
     */
    public function getAllCommands(): array
    {
        return $this->commands;
    }

    /**
     * Remove all commands for a module during uninstallation.
     *
     * Clears command entries registered by the specified module
     * from the in-memory registry.
     *
     * @param  string  $moduleName  The module name to clean up
     * @return array{deleted: int} Statistics about the cleanup operation
     */
    public function cleanup(string $moduleName): array
    {
        $count = count($this->commands[$moduleName] ?? []);
        unset($this->commands[$moduleName]);

        return ['deleted' => $count];
    }
}

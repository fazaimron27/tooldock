<?php

namespace App\Services\Registry;

use Closure;
use Illuminate\Http\Request;

/**
 * Registry for managing module-specific Inertia shared data.
 *
 * Allows modules to register callbacks that provide shared data
 * for Inertia responses. Each module can register its own data
 * without interfering with global shared data or other modules.
 */
class InertiaSharedDataRegistry
{
    /**
     * Registered shared data callbacks by module name.
     *
     * @var array<string, array<int, Closure(Request): array<string, mixed>>>
     */
    private array $callbacks = [];

    /**
     * Register a callback that provides shared data for a module.
     *
     * The callback receives the current Request and should return
     * an array of data to be merged into Inertia shared props.
     *
     * @param  string  $module  Module name (e.g., 'Vault', 'Blog')
     * @param  Closure(Request): array<string, mixed>  $callback  Callback that returns shared data
     */
    public function register(string $module, Closure $callback): void
    {
        if (! isset($this->callbacks[$module])) {
            $this->callbacks[$module] = [];
        }

        $this->callbacks[$module][] = $callback;
    }

    /**
     * Get all registered shared data for all modules.
     *
     * Executes all registered callbacks and merges their results.
     *
     * @param  Request  $request  The current request
     * @return array<string, mixed>
     */
    public function getSharedData(Request $request): array
    {
        $data = [];

        foreach ($this->callbacks as $module => $callbacks) {
            foreach ($callbacks as $callback) {
                $moduleData = $callback($request);
                $data = array_merge($data, $moduleData);
            }
        }

        return $data;
    }

    /**
     * Get shared data for a specific module.
     *
     * @param  string  $module  Module name
     * @param  Request  $request  The current request
     * @return array<string, mixed>
     */
    public function getModuleSharedData(string $module, Request $request): array
    {
        if (! isset($this->callbacks[$module])) {
            return [];
        }

        $data = [];

        foreach ($this->callbacks[$module] as $callback) {
            $moduleData = $callback($request);
            $data = array_merge($data, $moduleData);
        }

        return $data;
    }
}

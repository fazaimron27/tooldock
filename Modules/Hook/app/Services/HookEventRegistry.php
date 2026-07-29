<?php

/**
 * HookEventRegistry
 *
 * Central registry mapping trigger keys to Eloquent model lifecycle hooks.
 * Target modules require zero new code — Hook observes their models directly.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Hook\Services;

use App\Services\Registry\HookEventRegistryInterface;

/**
 * Class HookEventRegistry
 *
 * Stores hookable trigger definitions registered by per-module integration
 * registrars. Bound as a singleton in HookServiceProvider, implementing
 * the root-level HookEventRegistryInterface so other modules can resolve
 * it without coupling to this class directly.
 */
class HookEventRegistry implements HookEventRegistryInterface
{
    /**
     * Registered hookable triggers.
     *
     * Shape: [ trigger_key => [ label, modelClass, on, payloadSchema[] ] ]
     *
     * @var array<string, array{label: string, modelClass: class-string, on: string, payloadSchema: list<string>}>
     */
    private array $triggers = [];

    /**
     * Register a hookable model lifecycle trigger.
     *
     * @param  string  $key  Trigger key, e.g. 'treasury.transaction_created'
     * @param  string  $label  Human-readable label for the UI dropdown
     * @param  class-string  $modelClass  The Eloquent model class to observe
     * @param  string  $on  Eloquent lifecycle event: 'created'|'updated'|'deleted'
     * @param  list<string>  $payloadSchema  Attribute names exposed in the payload
     */
    public function register(
        string $key,
        string $label,
        string $modelClass,
        string $on = 'created',
        array $payloadSchema = [],
        ?callable $formatter = null,
    ): void {
        $this->triggers[$key] = compact('label', 'modelClass', 'on', 'payloadSchema', 'formatter');
    }

    /**
     * Get all registered triggers keyed by trigger string.
     *
     * @return array<string, array{label: string, modelClass: class-string, on: string, payloadSchema: list<string>}>
     */
    public function all(): array
    {
        return $this->triggers;
    }

    /**
     * Check whether any triggers are registered.
     */
    public function isEmpty(): bool
    {
        return empty($this->triggers);
    }

    /**
     * Get all unique model classes that have at least one trigger registered.
     *
     * @return list<class-string>
     */
    public function observedModelClasses(): array
    {
        return array_unique(array_column(array_values($this->triggers), 'modelClass'));
    }

    /**
     * Find the trigger key for a given model class + lifecycle event.
     *
     * @param  class-string  $modelClass
     */
    public function findKey(string $modelClass, string $on): ?string
    {
        foreach ($this->triggers as $key => $def) {
            if ($def['modelClass'] === $modelClass && $def['on'] === $on) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Get all trigger keys registered for a given model class.
     *
     * @param  class-string  $modelClass
     * @return array<string, array{label: string, on: string, payloadSchema: list<string>}>
     */
    public function triggersForModel(string $modelClass): array
    {
        return array_filter(
            $this->triggers,
            fn ($def) => $def['modelClass'] === $modelClass,
        );
    }
}

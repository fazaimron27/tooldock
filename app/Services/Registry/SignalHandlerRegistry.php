<?php

/**
 * Signal Handler Registry
 *
 * Central registry for signal handlers that manage registration,
 * event-based dispatch, and notification delivery through the
 * Signal module's queued notification system.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;

/**
 * Class SignalHandlerRegistry
 *
 * Manages signal handler registration and dispatching. Handlers
 * are registered by modules during boot, indexed by their subscribed
 * events for efficient lookup, and dispatched synchronously when
 * domain events occur. Extracts the target user from event data
 * and sends notifications via the Signal module.
 *
 * @see \App\Services\Registry\SignalHandlerInterface For handler contract
 * @see \Modules\Signal\Jobs\SendNotificationJob For notification delivery
 */
class SignalHandlerRegistry
{
    /**
     * @var array<int, string> Registered handler class names
     */
    private array $handlerClasses = [];

    /**
     * @var array<string, string> Handler class => module name mapping
     */
    private array $registeredBy = [];

    /**
     * @var array<string, array<int, string>> Event name => handler class names index
     */
    private array $eventIndex = [];

    /**
     * @var array<string, \App\Services\Registry\SignalHandlerInterface> Resolved handler instances
     */
    private array $instances = [];

    /**
     * Register a signal handler for a module.
     *
     * Validates the handler implements SignalHandlerInterface, checks
     * for duplicate registrations, resolves the handler instance to
     * build the event index, and stores the module association.
     *
     * @param  string  $module  Module name registering the handler
     * @param  string  $handlerClass  Fully-qualified handler class name
     * @return void
     *
     * @throws \InvalidArgumentException When the class does not implement SignalHandlerInterface
     * @throws \RuntimeException When the handler is already registered by a different module
     */
    public function register(string $module, string $handlerClass): void
    {
        if (! is_subclass_of($handlerClass, SignalHandlerInterface::class)) {
            throw new \InvalidArgumentException(
                "Class '{$handlerClass}' must implement SignalHandlerInterface."
            );
        }

        if (isset($this->registeredBy[$handlerClass])) {
            if ($this->registeredBy[$handlerClass] !== $module) {
                throw new \RuntimeException(
                    "Signal handler '{$handlerClass}' is already registered by module '{$this->registeredBy[$handlerClass]}'."
                );
            }

            return;
        }

        $this->handlerClasses[] = $handlerClass;
        $this->registeredBy[$handlerClass] = $module;

        $instance = $this->resolve($handlerClass);
        foreach ($instance->getEvents() as $event) {
            $this->eventIndex[$event][] = $handlerClass;
        }
    }

    /**
     * Register multiple signal handlers for a module.
     *
     * Convenience method that iterates over an array of handler
     * class names, delegating each to the `register()` method.
     *
     * @param  string  $module  Module name registering the handlers
     * @param  array<int, string>  $handlerClasses  Array of handler class names
     * @return void
     */
    public function registerMany(string $module, array $handlerClasses): void
    {
        foreach ($handlerClasses as $handlerClass) {
            $this->register($module, $handlerClass);
        }
    }

    /**
     * Check if any handlers are registered for a specific event.
     *
     * @param  string  $event  The event class name to check
     * @return bool True if at least one handler is registered for the event
     */
    public function hasHandlersForEvent(string $event): bool
    {
        return isset($this->eventIndex[$event]) && count($this->eventIndex[$event]) > 0;
    }

    /**
     * Dispatch an event to registered handlers.
     *
     * Delegates to `dispatchSync()` for synchronous processing.
     *
     * @param  string  $event  The event class name being dispatched
     * @param  mixed  $data  The event payload data
     * @param  bool  $sync  Reserved for future async support
     * @return void
     */
    public function dispatch(string $event, mixed $data, bool $sync = false): void
    {
        $this->dispatchSync($event, $data);
    }

    /**
     * Synchronously dispatch an event to all matching handlers.
     *
     * Resolves handlers for the event, checks `supports()`, calls
     * `handle()`, extracts the target user, and sends notifications.
     * Failures in individual handlers are logged and do not prevent
     * other handlers from executing (fail-open strategy).
     *
     * @param  string  $event  The event class name being dispatched
     * @param  mixed  $data  The event payload data
     * @param  bool  $dryRun  If true, evaluates handlers but skips sending notifications
     * @return array<string, array> Handler name => signal data for handlers that produced results
     */
    public function dispatchSync(string $event, mixed $data, bool $dryRun = false): array
    {
        $results = [];
        $handlerClasses = $this->eventIndex[$event] ?? [];
        $user = $this->extractUser($data);

        foreach ($handlerClasses as $handlerClass) {
            try {
                $handler = $this->resolve($handlerClass);

                if (! $handler->supports($event, $data)) {
                    continue;
                }

                $signal = $handler->handle($data);

                if ($signal !== null && $user) {
                    if (! $dryRun) {
                        $this->sendSignal($user, $signal, $handler->getModule());
                    }
                    $results[$handler->getName()] = $signal;
                }
            } catch (\Exception $e) {
                Log::debug("SignalHandlerRegistry: Handler {$handlerClass} failed", [
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Extract the target user from event data.
     *
     * Attempts to resolve a User instance from various data shapes:
     * direct User instance, Transaction model (via ->user), array
     * with 'user' key, or object with user() method or user property.
     *
     * @param  mixed  $data  The event payload data
     * @return \Modules\Core\Models\User|null The resolved user, or null if not found
     */
    private function extractUser(mixed $data): ?User
    {
        if ($data instanceof User) {
            return $data;
        }

        if ($data instanceof Transaction) {
            return $data->user;
        }

        if (is_array($data) && isset($data['user']) && $data['user'] instanceof User) {
            return $data['user'];
        }

        if (is_object($data) && method_exists($data, 'user')) {
            return $data->user;
        }

        if (is_object($data) && property_exists($data, 'user')) {
            return $data->user;
        }

        return null;
    }

    /**
     * Send a notification signal to a user.
     *
     * Dispatches a queued notification job via the Signal module
     * with the handler's signal data and module association.
     *
     * @param  \Modules\Core\Models\User  $user  The target user
     * @param  array  $signal  The notification payload from the handler
     * @param  string  $module  The originating module name
     * @return void
     */
    private function sendSignal(User $user, array $signal, string $module): void
    {
        \Modules\Signal\Jobs\SendNotificationJob::dispatch(
            $user->id,
            $signal['type'] ?? 'info',
            $signal['title'] ?? '',
            $signal['message'] ?? '',
            $signal['url'] ?? null,
            $module,
            $signal['category'] ?? null,
            $signal['delivery'] ?? \Modules\Signal\Services\SignalService::DELIVERY_SILENT,
            $signal['action'] ?? null,
        );
    }

    /**
     * Resolve a handler class to an instance via the container.
     *
     * Caches resolved instances to avoid repeated container lookups
     * during the same request lifecycle.
     *
     * @param  string  $handlerClass  Fully-qualified handler class name
     * @return \App\Services\Registry\SignalHandlerInterface The resolved handler instance
     */
    private function resolve(string $handlerClass): SignalHandlerInterface
    {
        if (! isset($this->instances[$handlerClass])) {
            $this->instances[$handlerClass] = app($handlerClass);
        }

        return $this->instances[$handlerClass];
    }

    /**
     * Get all registered handler class names.
     *
     * @return array<int, string> All handler class names
     */
    public function getAll(): array
    {
        return $this->handlerClasses;
    }

    /**
     * Get handlers registered by a specific module.
     *
     * @param  string  $module  Module name to filter by
     * @return array<int, string> Handler class names for the module
     */
    public function getByModule(string $module): array
    {
        return array_filter(
            $this->handlerClasses,
            fn ($class) => ($this->registeredBy[$class] ?? '') === $module
        );
    }

    /**
     * Get handlers registered for a specific event.
     *
     * @param  string  $event  Event class name to look up
     * @return array<int, string> Handler class names for the event
     */
    public function getByEvent(string $event): array
    {
        return $this->eventIndex[$event] ?? [];
    }

    /**
     * Get all events that have registered handlers.
     *
     * @return array<int, string> Event class names with at least one handler
     */
    public function getEvents(): array
    {
        return array_keys($this->eventIndex);
    }

    /**
     * Get the total number of registered handlers.
     *
     * @return int Handler count
     */
    public function count(): int
    {
        return count($this->handlerClasses);
    }

    /**
     * Check if a handler class is registered.
     *
     * @param  string  $handlerClass  Fully-qualified handler class name
     * @return bool True if the handler is registered
     */
    public function has(string $handlerClass): bool
    {
        return isset($this->registeredBy[$handlerClass]);
    }

    /**
     * Clear all registered handlers and indexes.
     *
     * Resets the registry to its initial empty state. Primarily
     * used in testing.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->handlerClasses = [];
        $this->registeredBy = [];
        $this->eventIndex = [];
        $this->instances = [];
    }
}

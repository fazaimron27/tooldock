<?php

/**
 * Signal Handler Interface
 *
 * Contract for signal handlers that evaluate conditions
 * against domain events and return notification signal data
 * for the Signal module's notification system.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Registry;

/**
 * Interface SignalHandlerInterface
 *
 * Defines the contract for signal handlers registered with the
 * SignalHandlerRegistry. Handlers subscribe to domain events,
 * evaluate conditions against event data, and produce notification
 * payloads when conditions are met.
 *
 * @see \App\Services\Registry\SignalHandlerRegistry For handler registration and dispatch
 * @see \Modules\Signal\Jobs\SendNotificationJob For the notification delivery mechanism
 */
interface SignalHandlerInterface
{
    /**
     * Get the list of domain events this handler responds to.
     *
     * Returns fully-qualified event class names that this handler
     * should be invoked for during signal dispatch.
     *
     * @return array<int, string> Array of event class names
     */
    public function getEvents(): array;

    /**
     * Get the module name that registered this handler.
     *
     * Used for grouping, filtering, and associating notifications
     * with the originating module.
     *
     * @return string Module name (e.g., 'Treasury', 'Blog')
     */
    public function getModule(): string;

    /**
     * Get the unique name identifier for this handler.
     *
     * Used as a key in dispatch results and for debugging.
     *
     * @return string Handler name (e.g., 'wallet_balance', 'goal_milestone')
     */
    public function getName(): string;

    /**
     * Check if this handler can process the given event and data.
     *
     * Called before `handle()` to determine if the handler is
     * applicable for the current event and data context. Allows
     * handlers to filter based on data type or content.
     *
     * @param  string  $event  The event class name being dispatched
     * @param  mixed  $data  The event payload data
     * @return bool True if this handler should process the event
     */
    public function supports(string $event, mixed $data): bool;

    /**
     * Process the event data and return a notification signal.
     *
     * Evaluates conditions and produces a notification payload
     * if applicable. Returns null if no notification should be sent.
     *
     * @param  mixed  $data  The event payload data to evaluate
     * @return array{type: string, title: string, message: string, url: ?string, category: ?string, delivery: ?string, action: ?string}|null Notification signal data, or null to skip
     */
    public function handle(mixed $data): ?array;
}

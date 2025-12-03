<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit breaker for cache operations.
 *
 * Prevents cascading failures by stopping cache operations after repeated failures.
 * Automatically recovers after a cooldown period.
 *
 * **States:**
 * - Closed: Normal operation, cache requests are allowed
 * - Open: Too many failures, cache requests are blocked
 * - Half-Open: Testing if cache has recovered, allowing limited requests
 */
class CircuitBreaker
{
    private const STATE_CLOSED = 'closed';

    private const STATE_OPEN = 'open';

    private const STATE_HALF_OPEN = 'half_open';

    private const CACHE_PREFIX = 'circuit_breaker';

    /**
     * Create a new circuit breaker instance.
     *
     * @param  string  $name  Circuit breaker name (e.g., 'cache', 'redis')
     * @param  int  $failureThreshold  Number of failures before opening circuit
     * @param  int  $timeout  Seconds to wait before attempting recovery (half-open state)
     * @param  int  $successThreshold  Number of successes in half-open state to close circuit
     */
    public function __construct(
        private string $name,
        private int $failureThreshold = 5,
        private int $timeout = 60,
        private int $successThreshold = 2
    ) {}

    /**
     * Check if the circuit allows the operation.
     *
     * @return bool True if operation is allowed, false if circuit is open
     */
    public function allowsRequest(): bool
    {
        $state = $this->getState();

        if ($state === self::STATE_CLOSED) {
            return true;
        }

        if ($state === self::STATE_OPEN) {
            // Check if timeout has passed, transition to half-open
            if ($this->shouldAttemptRecovery()) {
                $this->setState(self::STATE_HALF_OPEN);
                Log::info('CircuitBreaker: Transitioning to half-open state', [
                    'name' => $this->name,
                ]);

                return true;
            }

            return false;
        }

        // Half-open state: allow limited requests
        return true;
    }

    /**
     * Record a successful operation.
     *
     * @return void
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $successes = $this->getSuccessCount() + 1;
            $this->setSuccessCount($successes);

            if ($successes >= $this->successThreshold) {
                $this->setState(self::STATE_CLOSED);
                $this->resetCounters();
                Log::info('CircuitBreaker: Circuit closed after successful recovery', [
                    'name' => $this->name,
                ]);
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count on success
            $this->resetFailureCount();
        }
    }

    /**
     * Record a failed operation.
     *
     * @return void
     */
    public function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // Failure in half-open state, immediately open circuit
            $this->setState(self::STATE_OPEN);
            $this->setLastFailureTime(time());
            $this->resetCounters();
            Log::warning('CircuitBreaker: Circuit opened after failure in half-open state', [
                'name' => $this->name,
            ]);
        } else {
            // Increment failure count
            $failures = $this->getFailureCount() + 1;
            $this->setFailureCount($failures);
            $this->setLastFailureTime(time());

            if ($failures >= $this->failureThreshold) {
                $this->setState(self::STATE_OPEN);
                Log::error('CircuitBreaker: Circuit opened due to repeated failures', [
                    'name' => $this->name,
                    'failures' => $failures,
                    'threshold' => $this->failureThreshold,
                ]);
            }
        }
    }

    /**
     * Get the current state of the circuit.
     *
     * @return string Current state (closed, open, half_open)
     */
    public function getState(): string
    {
        return Cache::get($this->getStateKey(), self::STATE_CLOSED);
    }

    /**
     * Set the state of the circuit.
     *
     * @param  string  $state  New state
     * @return void
     */
    private function setState(string $state): void
    {
        Cache::put($this->getStateKey(), $state, $this->timeout * 2);
    }

    /**
     * Check if recovery should be attempted (timeout has passed).
     *
     * @return bool True if recovery should be attempted
     */
    private function shouldAttemptRecovery(): bool
    {
        $lastFailure = $this->getLastFailureTime();

        if ($lastFailure === null) {
            return true;
        }

        return (time() - $lastFailure) >= $this->timeout;
    }

    /**
     * Get the failure count.
     *
     * @return int Number of failures
     */
    private function getFailureCount(): int
    {
        return Cache::get($this->getFailureCountKey(), 0);
    }

    /**
     * Set the failure count.
     *
     * @param  int  $count  Failure count
     * @return void
     */
    private function setFailureCount(int $count): void
    {
        Cache::put($this->getFailureCountKey(), $count, $this->timeout * 2);
    }

    /**
     * Reset the failure count.
     *
     * @return void
     */
    private function resetFailureCount(): void
    {
        Cache::forget($this->getFailureCountKey());
    }

    /**
     * Get the success count (for half-open state).
     *
     * @return int Number of successes
     */
    private function getSuccessCount(): int
    {
        return Cache::get($this->getSuccessCountKey(), 0);
    }

    /**
     * Set the success count.
     *
     * @param  int  $count  Success count
     * @return void
     */
    private function setSuccessCount(int $count): void
    {
        Cache::put($this->getSuccessCountKey(), $count, $this->timeout * 2);
    }

    /**
     * Reset all counters.
     *
     * @return void
     */
    private function resetCounters(): void
    {
        Cache::forget($this->getFailureCountKey());
        Cache::forget($this->getSuccessCountKey());
    }

    /**
     * Get the last failure time.
     *
     * @return int|null Timestamp of last failure or null
     */
    private function getLastFailureTime(): ?int
    {
        return Cache::get($this->getLastFailureTimeKey());
    }

    /**
     * Set the last failure time.
     *
     * @param  int  $time  Timestamp
     * @return void
     */
    private function setLastFailureTime(int $time): void
    {
        Cache::put($this->getLastFailureTimeKey(), $time, $this->timeout * 2);
    }

    /**
     * Get cache key for state.
     *
     * @return string Cache key
     */
    private function getStateKey(): string
    {
        return self::CACHE_PREFIX.':'.$this->name.':state';
    }

    /**
     * Get cache key for failure count.
     *
     * @return string Cache key
     */
    private function getFailureCountKey(): string
    {
        return self::CACHE_PREFIX.':'.$this->name.':failures';
    }

    /**
     * Get cache key for success count.
     *
     * @return string Cache key
     */
    private function getSuccessCountKey(): string
    {
        return self::CACHE_PREFIX.':'.$this->name.':successes';
    }

    /**
     * Get cache key for last failure time.
     *
     * @return string Cache key
     */
    private function getLastFailureTimeKey(): string
    {
        return self::CACHE_PREFIX.':'.$this->name.':last_failure';
    }

    /**
     * Reset the circuit breaker (for testing or manual recovery).
     *
     * @return void
     */
    public function reset(): void
    {
        $this->setState(self::STATE_CLOSED);
        $this->resetCounters();
        Cache::forget($this->getLastFailureTimeKey());
    }
}

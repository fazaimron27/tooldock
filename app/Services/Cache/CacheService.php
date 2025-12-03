<?php

namespace App\Services\Cache;

use App\Services\Cache\Exceptions\CacheConnectionException;
use App\Services\Cache\Exceptions\CacheException;
use App\Services\Cache\Exceptions\CacheTagException;
use App\Services\Cache\Exceptions\CacheTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Centralized cache management service optimized for Redis.
 *
 * Provides consistent cache operations with tag support, error handling,
 * and logging across the application. Assumes Redis is always used,
 * which supports cache tags for efficient invalidation.
 *
 * **Key Features:**
 * - Automatic error handling with graceful fallbacks
 * - Redis tag support for selective cache invalidation
 * - Context-aware logging for debugging
 * - Consistent API across all cache operations
 *
 * **Error Handling:**
 * All methods include automatic error handling with fallback behavior:
 * - remember/rememberForever: Execute callback directly on cache failure
 * - forget/flush: Log warning and return false on failure
 * - get: Return default value on failure
 * - put: Return false on failure
 *
 * **Cache Tags:**
 * Cache tags allow selective invalidation of related cache entries. When using tags:
 * - Store with tags: `remember('key', 3600, $callback, 'tag')`
 * - Retrieve with same tags: `get('key', $default, 'tag')`
 * - Clear by tag: `flush('tag')` - removes ALL keys with that tag
 *
 * **Multi-Tag Strategy:**
 * For selective clearing, use multiple tags (e.g., ['global', 'module:blog']).
 * This allows clearing only module-specific cache while keeping global cache intact.
 *
 * @see https://laravel.com/docs/cache#cache-tags Laravel Cache Tags Documentation
 */
class CacheService
{
    private ?CircuitBreaker $circuitBreaker = null;

    /**
     * Create a new cache service instance.
     *
     * @param  CacheMetricsService|null  $metrics  Optional metrics service for performance tracking
     */
    public function __construct(
        private ?CacheMetricsService $metrics = null
    ) {
        // Initialize metrics service if enabled in config
        if ($this->isMetricsEnabled() && $this->metrics === null) {
            $this->metrics = app(CacheMetricsService::class);
        }

        // Initialize circuit breaker if enabled
        if ($this->isCircuitBreakerEnabled()) {
            $this->circuitBreaker = new CircuitBreaker(
                'cache',
                Config::get('cache-metrics.circuit_breaker_failure_threshold', 5),
                Config::get('cache-metrics.circuit_breaker_timeout', 60),
                Config::get('cache-metrics.circuit_breaker_success_threshold', 2)
            );
        }
    }

    /**
     * Check if metrics collection is enabled.
     *
     * @return bool True if metrics are enabled
     */
    private function isMetricsEnabled(): bool
    {
        return Config::get('cache-metrics.enabled', false);
    }

    /**
     * Check if retry logic is enabled.
     *
     * @return bool True if retry is enabled
     */
    private function isRetryEnabled(): bool
    {
        return Config::get('cache-metrics.retry_enabled', true);
    }

    /**
     * Check if circuit breaker is enabled.
     *
     * @return bool True if circuit breaker is enabled
     */
    private function isCircuitBreakerEnabled(): bool
    {
        return Config::get('cache-metrics.circuit_breaker_enabled', true);
    }

    /**
     * Check if an exception is a transient error (can be retried).
     *
     * @param  \Throwable  $e  Exception to check
     * @return bool True if error is transient
     */
    private function isTransientError(\Throwable $e): bool
    {
        return $e instanceof CacheConnectionException || $e instanceof CacheTimeoutException;
    }

    /**
     * Classify an exception into a specific cache exception type.
     *
     * @param  \Throwable  $e  Exception to classify
     * @return CacheException Classified exception
     */
    private function classifyException(\Throwable $e): CacheException
    {
        // Check if already a cache exception
        if ($e instanceof CacheException) {
            return $e;
        }

        $message = $e->getMessage();
        $lowerMessage = strtolower($message);

        // Connection errors
        if (
            str_contains($lowerMessage, 'connection') ||
            str_contains($lowerMessage, 'refused') ||
            str_contains($lowerMessage, 'unreachable') ||
            str_contains($lowerMessage, 'no connection')
        ) {
            return new CacheConnectionException($message, $e->getCode(), $e);
        }

        // Timeout errors
        if (
            str_contains($lowerMessage, 'timeout') ||
            str_contains($lowerMessage, 'timed out')
        ) {
            return new CacheTimeoutException($message, $e->getCode(), $e);
        }

        // Tag errors
        if (
            str_contains($lowerMessage, 'tag') ||
            str_contains($lowerMessage, 'tags not supported')
        ) {
            return new CacheTagException($message, $e->getCode(), $e);
        }

        // Default to connection exception for unknown errors (assume transient)
        return new CacheConnectionException($message, $e->getCode(), $e);
    }

    /**
     * Execute a cache operation with retry logic.
     *
     * @param  \Closure  $operation  Operation to execute
     * @param  string  $operationName  Name of operation for logging
     * @param  string|null  $context  Optional context
     * @return mixed Operation result
     */
    private function executeWithRetry(\Closure $operation, string $operationName, ?string $context = null): mixed
    {
        $maxRetries = Config::get('cache-metrics.max_retries', 3);
        $baseDelay = Config::get('cache-metrics.retry_base_delay_ms', 100);

        $lastException = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                // Check circuit breaker before attempting
                if ($this->circuitBreaker !== null && ! $this->circuitBreaker->allowsRequest()) {
                    throw new CacheConnectionException('Circuit breaker is open - cache operations are temporarily disabled');
                }

                $result = $operation();

                // Record success in circuit breaker
                if ($this->circuitBreaker !== null) {
                    $this->circuitBreaker->recordSuccess();
                }

                return $result;
            } catch (\Throwable $e) {
                $lastException = $e;
                $classifiedException = $this->classifyException($e);

                // Record failure in circuit breaker
                if ($this->circuitBreaker !== null) {
                    $this->circuitBreaker->recordFailure();
                }

                // Only retry transient errors
                if (! $this->isTransientError($classifiedException) || ! $this->isRetryEnabled()) {
                    throw $classifiedException;
                }

                // Don't retry on last attempt
                if ($attempt >= $maxRetries) {
                    break;
                }

                // Exponential backoff: delay = base * (2 ^ attempt)
                $delay = $baseDelay * (2 ** $attempt);
                usleep($delay * 1000); // Convert to microseconds

                Log::debug('CacheService: Retrying operation after transient error', [
                    'operation' => $operationName,
                    'attempt' => $attempt + 1,
                    'max_retries' => $maxRetries,
                    'delay_ms' => $delay,
                    'error' => $classifiedException->getMessage(),
                    'context' => $context,
                ]);
            }
        }

        // All retries exhausted
        throw $this->classifyException($lastException);
    }

    /**
     * Record metrics for an operation.
     *
     * @param  string  $operation  Operation type
     * @param  float  $duration  Duration in milliseconds
     * @param  bool  $hit  Whether it was a cache hit
     * @param  string|null  $context  Optional context
     * @return void
     */
    private function recordMetrics(string $operation, float $duration, bool $hit, ?string $context = null): void
    {
        if (! $this->isMetricsEnabled() || $this->metrics === null) {
            return;
        }

        if ($hit) {
            $this->metrics->recordHit($operation, $duration, $context);
        } else {
            $this->metrics->recordMiss($operation, $duration, $context);
        }
    }

    /**
     * Record metrics for a write operation.
     *
     * @param  string  $operation  Operation type
     * @param  float  $duration  Duration in milliseconds
     * @param  string|null  $context  Optional context
     * @return void
     */
    private function recordWriteMetrics(string $operation, float $duration, ?string $context = null): void
    {
        if (! $this->isMetricsEnabled() || $this->metrics === null) {
            return;
        }

        $this->metrics->recordWrite($operation, $duration, $context);
    }

    /**
     * Record metrics for a delete operation.
     *
     * @param  string  $operation  Operation type
     * @param  float  $duration  Duration in milliseconds
     * @param  string|null  $context  Optional context
     * @return void
     */
    private function recordDeleteMetrics(string $operation, float $duration, ?string $context = null): void
    {
        if (! $this->isMetricsEnabled() || $this->metrics === null) {
            return;
        }

        $this->metrics->recordDelete($operation, $duration, $context);
    }

    /**
     * Record metrics for an error.
     *
     * @param  string  $operation  Operation type
     * @param  string  $error  Error message
     * @param  string|null  $context  Optional context
     * @return void
     */
    private function recordErrorMetrics(string $operation, string $error, ?string $context = null): void
    {
        if (! $this->isMetricsEnabled() || $this->metrics === null) {
            return;
        }

        $this->metrics->recordError($operation, $error, $context);
    }

    /**
     * Check if cache tags are supported by the current cache driver.
     *
     * @return bool True if tags are supported
     */
    public function supportsTags(): bool
    {
        $store = Cache::store();

        return method_exists($store, 'tags');
    }

    /**
     * Get a tagged cache instance.
     *
     * @param  string|array  $tags  Cache tag(s)
     * @return \Illuminate\Contracts\Cache\Repository Tagged cache repository
     */
    public function tags(string|array $tags): \Illuminate\Contracts\Cache\Repository
    {
        $tags = is_array($tags) ? $tags : [$tags];

        if ($this->supportsTags()) {
            return Cache::tags($tags);
        }

        // Fallback to non-tagged cache if tags not supported
        return Cache::store();
    }

    /**
     * Remember a value in cache with optional tags.
     *
     * Caches the result of the callback for the specified TTL. If the key exists,
     * returns the cached value. Otherwise, executes the callback and caches the result.
     * Automatically falls back to executing the callback directly if cache fails.
     *
     * @param  string  $key  Cache key
     * @param  int|\DateTimeInterface|\DateInterval|null  $ttl  Time to live
     * @param  \Closure  $callback  Value generator
     * @param  string|array|null  $tags  Optional cache tags for selective invalidation
     * @return mixed Cached value
     *
     * @example
     * // Cache without tags (standard caching)
     * $value = $cacheService->remember('user:123', 3600, fn() => User::find(123));
     * @example
     * // Cache with single tag
     * $value = $cacheService->remember('user:123', 3600, fn() => User::find(123), 'users');
     * @example
     * // Cache with multiple tags for selective invalidation
     * $value = $cacheService->remember('user:123', 3600, fn() => User::find(123), ['users', 'dashboard']);
     */
    public function remember(string $key, int|\DateTimeInterface|\DateInterval|null $ttl, \Closure $callback, string|array|null $tags = null, ?string $context = null): mixed
    {
        $startTime = microtime(true);
        $callbackExecuted = false;

        try {
            // Wrap callback to track if it was executed (cache miss)
            $wrappedCallback = function () use ($callback, &$callbackExecuted) {
                $callbackExecuted = true; // Cache miss - callback executing

                return $callback();
            };

            $result = $this->executeWithRetry(function () use ($key, $ttl, $wrappedCallback, $tags) {
                return $tags !== null
                    ? $this->tags($tags)->remember($key, $ttl, $wrappedCallback)
                    : Cache::remember($key, $ttl, $wrappedCallback);
            }, 'remember', $context);

            $duration = (microtime(true) - $startTime) * 1000;
            $hit = ! $callbackExecuted; // If callback didn't execute, it was a cache hit
            $this->recordMetrics('remember', $duration, $hit, $context);

            if (! $hit) {
                $this->recordWriteMetrics('remember', $duration, $context);
            }

            return $result;
        } catch (CacheException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('remember', $e->getMessage(), $context);

            Log::warning('CacheService: Cache remember failed, executing callback directly', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ]);

            return $callback();
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('remember', $e->getMessage(), $context);

            Log::warning('CacheService: Cache remember failed with unexpected error, executing callback directly', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * Remember a value forever in cache with optional tags.
     *
     * Caches the result of the callback indefinitely until manually cleared.
     * Useful for data that rarely changes (e.g., application settings, configuration).
     * Automatically falls back to executing the callback directly if cache fails.
     *
     * @param  string  $key  Cache key
     * @param  \Closure  $callback  Value generator
     * @param  string|array|null  $tags  Optional cache tags for selective invalidation
     * @return mixed Cached value
     *
     * @example
     * // Cache settings forever with tag for easy invalidation
     * $settings = $cacheService->rememberForever('app_settings', function() {
     *     return Setting::all();
     * }, 'settings');
     * @example
     * // Cache with multiple tags
     * $data = $cacheService->rememberForever('config', fn() => Config::all(), ['config', 'app']);
     */
    public function rememberForever(string $key, \Closure $callback, string|array|null $tags = null, ?string $context = null): mixed
    {
        $startTime = microtime(true);
        $callbackExecuted = false;

        try {
            // Wrap callback to track if it was executed (cache miss)
            $wrappedCallback = function () use ($callback, &$callbackExecuted) {
                $callbackExecuted = true; // Cache miss - callback executing

                return $callback();
            };

            $result = $this->executeWithRetry(function () use ($key, $wrappedCallback, $tags) {
                return $tags !== null
                    ? $this->tags($tags)->rememberForever($key, $wrappedCallback)
                    : Cache::rememberForever($key, $wrappedCallback);
            }, 'rememberForever', $context);

            $duration = (microtime(true) - $startTime) * 1000;
            $hit = ! $callbackExecuted; // If callback didn't execute, it was a cache hit
            $this->recordMetrics('rememberForever', $duration, $hit, $context);

            if (! $hit) {
                $this->recordWriteMetrics('rememberForever', $duration, $context);
            }

            return $result;
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('rememberForever', $e->getMessage(), $context);

            Log::warning('CacheService: Cache rememberForever failed, executing callback directly', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * Forget a cache key with optional tags.
     *
     * Removes a specific cache key. If tags are provided, the key must be accessed
     * with the same tags that were used when storing it.
     *
     * @param  string  $key  Cache key
     * @param  string|array|null  $tags  Optional cache tags (must match tags used when storing)
     * @return bool True if key was forgotten
     *
     * @example
     * // Forget a key without tags
     * $cacheService->forget('user:123');
     * @example
     * // Forget a tagged key (must use same tags as when stored)
     * $cacheService->forget('user:123', 'users');
     * @example
     * // Forget a key with multiple tags
     * $cacheService->forget('user:123', ['users', 'dashboard']);
     */
    public function forget(string $key, string|array|null $tags = null, ?string $context = null): bool
    {
        $startTime = microtime(true);

        try {
            $result = $this->executeWithRetry(function () use ($key, $tags) {
                return $tags !== null
                    ? $this->tags($tags)->forget($key)
                    : Cache::forget($key);
            }, 'forget', $context);

            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordDeleteMetrics('forget', $duration, $context);

            return $result;
        } catch (CacheException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('forget', $e->getMessage(), $context);

            Log::warning('CacheService: Cache forget failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ]);

            return false;
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('forget', $e->getMessage(), $context);

            Log::warning('CacheService: Cache forget failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Flush cache with optional tags.
     *
     * Removes all cache entries. If tags are provided, only entries with those tags
     * are removed. If no tags are provided, ALL cache entries are removed (use with caution).
     *
     * **Important:** When using Redis tags, flushing a tag removes ALL keys with that tag.
     * For selective clearing, use multiple tags (e.g., ['dashboard_widgets', 'dashboard_widgets:blog'])
     * and flush only the specific tag you want to clear.
     *
     * @param  string|array|null  $tags  Optional cache tags to flush (if null, flushes all cache)
     * @param  string|null  $context  Context for logging (e.g., 'Settings', 'Menu', 'DashboardWidgetRegistry')
     * @return bool True if flush was successful
     *
     * @example
     * // Flush all cache (use with caution!)
     * $cacheService->flush(null, 'Maintenance');
     * @example
     * // Flush all entries with a specific tag
     * $cacheService->flush('settings', 'SettingsService');
     * @example
     * // Flush entries with any of the provided tags
     * $cacheService->flush(['users', 'dashboard'], 'UserObserver');
     * @example
     * // Module-specific clearing (using multi-tag strategy)
     * // Widgets are tagged with: ['dashboard_widgets', 'dashboard_widgets:blog']
     * // Flushing 'dashboard_widgets:blog' only clears Blog widgets
     * $cacheService->flush('dashboard_widgets:blog', 'DashboardWidgetRegistry');
     */
    public function flush(string|array|null $tags = null, ?string $context = null): bool
    {
        $startTime = microtime(true);

        try {
            $result = $this->executeWithRetry(function () use ($tags) {
                if ($tags !== null && $this->supportsTags()) {
                    $tags = is_array($tags) ? $tags : [$tags];
                    Cache::tags($tags)->flush();

                    return true;
                }

                // Fallback: flush all cache (use with caution)
                Cache::flush();

                return true;
            }, 'flush', $context);

            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordDeleteMetrics('flush', $duration, $context);

            Log::debug('CacheService: Flushed cache', [
                'tags' => $tags,
                'context' => $context,
            ]);

            return $result;
        } catch (CacheException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('flush', $e->getMessage(), $context);

            Log::warning('CacheService: Cache flush failed', [
                'tags' => $tags,
                'context' => $context,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ]);

            return false;
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('flush', $e->getMessage(), $context);

            Log::warning('CacheService: Cache flush failed', [
                'tags' => $tags,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clear cache for a specific tag (convenience method).
     *
     * Convenience method that calls flush() with a single tag.
     * Useful for clearing all cache entries associated with a specific tag.
     *
     * @param  string  $tag  Cache tag to clear
     * @param  string|null  $context  Context for logging
     * @return bool True if flush was successful
     *
     * @example
     * // Clear all settings cache
     * $cacheService->clearTag('settings', 'SettingsService');
     * @example
     * // Clear all menu cache
     * $cacheService->clearTag('menus', 'MenuRegistry');
     */
    public function clearTag(string $tag, ?string $context = null): bool
    {
        return $this->flush($tag, $context);
    }

    /**
     * Put a value in cache with optional tags.
     *
     * Stores a value in cache with the specified TTL. Unlike remember(), this method
     * does not check if the key exists first - it always stores the value.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @param  int|\DateTimeInterface|\DateInterval|null  $ttl  Time to live (null = forever)
     * @param  string|array|null  $tags  Optional cache tags for selective invalidation
     * @return bool True if value was cached
     *
     * @example
     * // Store a value without tags
     * $cacheService->put('user:123', $user, 3600);
     * @example
     * // Store with tag
     * $cacheService->put('user:123', $user, 3600, 'users');
     * @example
     * // Store forever with tags
     * $cacheService->put('config:app', $config, null, 'config');
     */
    public function put(string $key, mixed $value, int|\DateTimeInterface|\DateInterval|null $ttl = null, string|array|null $tags = null, ?string $context = null): bool
    {
        $startTime = microtime(true);

        try {
            $result = $tags !== null
                ? $this->tags($tags)->put($key, $value, $ttl)
                : Cache::put($key, $value, $ttl);

            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordWriteMetrics('put', $duration, $context);

            return $result;
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('put', $e->getMessage(), $context);

            Log::warning('CacheService: Cache put failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get a value from cache with optional tags.
     *
     * Retrieves a value from cache. If the key doesn't exist or cache fails,
     * returns the default value. If tags are provided, the key must be accessed
     * with the same tags that were used when storing it.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $default  Default value if key not found
     * @param  string|array|null  $tags  Optional cache tags (must match tags used when storing)
     * @return mixed Cached value or default
     *
     * @example
     * // Get without tags
     * $user = $cacheService->get('user:123', null);
     * @example
     * // Get with tag (must match tag used when storing)
     * $user = $cacheService->get('user:123', null, 'users');
     * @example
     * // Get with default value
     * $count = $cacheService->get('post_count', 0, 'stats');
     */
    public function get(string $key, mixed $default = null, string|array|null $tags = null, ?string $context = null): mixed
    {
        $startTime = microtime(true);
        $hit = false;

        try {
            $result = $this->executeWithRetry(function () use ($key, $default, $tags) {
                return $tags !== null
                    ? $this->tags($tags)->get($key, $default)
                    : Cache::get($key, $default);
            }, 'get', $context);

            // Determine if it was a hit (value found) or miss (default returned)
            // Note: This check might not be perfect if default value equals cached value
            $hit = $result !== $default || ($tags !== null ? $this->tags($tags)->has($key) : Cache::has($key));

            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordMetrics('get', $duration, $hit, $context);

            return $result;
        } catch (CacheException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('get', $e->getMessage(), $context);

            Log::warning('CacheService: Cache get failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ]);

            return $default;
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->recordErrorMetrics('get', $e->getMessage(), $context);

            Log::warning('CacheService: Cache get failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }
}

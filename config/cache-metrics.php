<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for cache performance metrics collection.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Metrics Collection
    |--------------------------------------------------------------------------
    |
    | Whether to collect cache performance metrics.
    | When enabled, CacheService will track hit/miss rates, operation durations,
    | and other performance metrics.
    |
    | Default: false (disabled by default for performance)
    |
    */

    'enabled' => env('CACHE_METRICS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Slow Operation Threshold
    |--------------------------------------------------------------------------
    |
    | Duration in milliseconds above which cache operations are considered slow.
    | Slow operations will be logged as warnings.
    |
    | Default: 100 milliseconds
    |
    */

    'slow_threshold_ms' => env('CACHE_METRICS_SLOW_THRESHOLD', 100),

    /*
    |--------------------------------------------------------------------------
    | Metrics TTL
    |--------------------------------------------------------------------------
    |
    | Time to live for metrics data in seconds.
    | Metrics older than this will be automatically cleared.
    |
    | Default: 3600 seconds (1 hour)
    |
    */

    'ttl' => env('CACHE_METRICS_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for retrying failed cache operations.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Retry Logic
    |--------------------------------------------------------------------------
    |
    | Whether to retry failed cache operations.
    | Only transient errors (connection, timeout) will be retried.
    |
    | Default: true
    |
    */

    'retry_enabled' => env('CACHE_RETRY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Max Retry Attempts
    |--------------------------------------------------------------------------
    |
    | Maximum number of retry attempts for transient errors.
    |
    | Default: 3
    |
    */

    'max_retries' => env('CACHE_MAX_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Retry Base Delay
    |--------------------------------------------------------------------------
    |
    | Base delay in milliseconds for exponential backoff.
    | Delay = base_delay * (2 ^ attempt_number)
    |
    | Default: 100 milliseconds
    |
    */

    'retry_base_delay_ms' => env('CACHE_RETRY_BASE_DELAY', 100),

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for circuit breaker pattern.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Whether to use circuit breaker to prevent cascading failures.
    |
    | Default: true
    |
    */

    'circuit_breaker_enabled' => env('CACHE_CIRCUIT_BREAKER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Failure Threshold
    |--------------------------------------------------------------------------
    |
    | Number of consecutive failures before opening the circuit.
    |
    | Default: 5
    |
    */

    'circuit_breaker_failure_threshold' => env('CACHE_CIRCUIT_BREAKER_FAILURE_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait before attempting recovery (half-open state).
    |
    | Default: 60 seconds
    |
    */

    'circuit_breaker_timeout' => env('CACHE_CIRCUIT_BREAKER_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Success Threshold
    |--------------------------------------------------------------------------
    |
    | Number of successful operations in half-open state to close circuit.
    |
    | Default: 2
    |
    */

    'circuit_breaker_success_threshold' => env('CACHE_CIRCUIT_BREAKER_SUCCESS_THRESHOLD', 2),
];

<?php

/**
 * Cache Connection Exception.
 *
 * Thrown when the cache backend connection fails. This is typically a transient
 * error (e.g., Redis connection refused, network timeout) that may be retried
 * by the CacheService's retry logic or circuit breaker.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Services\Cache\Exceptions;

/**
 * Exception thrown when cache connection fails.
 *
 * This is typically a transient error that may be retried.
 * Examples: Redis connection refused, network timeout, server unavailable.
 */
class CacheConnectionException extends CacheException {}

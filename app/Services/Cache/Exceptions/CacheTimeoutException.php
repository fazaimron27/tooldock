<?php

/**
 * Cache Timeout Exception.
 *
 * Thrown when a cache operation times out. This is typically a transient
 * error (e.g., Redis command timeout, slow network response) that may
 * be retried by the CacheService's retry logic.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Services\Cache\Exceptions;

/**
 * Exception thrown when cache operation times out.
 *
 * This is typically a transient error that may be retried.
 * Examples: Redis command timeout, slow network response.
 */
class CacheTimeoutException extends CacheException {}

<?php

namespace App\Services\Cache\Exceptions;

/**
 * Exception thrown when cache connection fails.
 *
 * This is typically a transient error that may be retried.
 * Examples: Redis connection refused, network timeout, server unavailable.
 */
class CacheConnectionException extends CacheException {}

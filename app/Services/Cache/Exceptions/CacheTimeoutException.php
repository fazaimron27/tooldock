<?php

namespace App\Services\Cache\Exceptions;

/**
 * Exception thrown when cache operation times out.
 *
 * This is typically a transient error that may be retried.
 * Examples: Redis command timeout, slow network response.
 */
class CacheTimeoutException extends CacheException {}

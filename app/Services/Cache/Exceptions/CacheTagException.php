<?php

namespace App\Services\Cache\Exceptions;

/**
 * Exception thrown when cache tag operations fail.
 *
 * This is typically a non-transient error that should not be retried.
 * Examples: Invalid tag format, tag not supported by driver.
 */
class CacheTagException extends CacheException {}

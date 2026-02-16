<?php

/**
 * Cache Exception.
 *
 * Base exception class for all cache-related errors. Extended by specific
 * cache exception types (connection, timeout, tag) for granular error
 * handling and retry decisions.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Services\Cache\Exceptions;

use Exception;

/**
 * Base exception for cache-related errors.
 */
class CacheException extends Exception {}

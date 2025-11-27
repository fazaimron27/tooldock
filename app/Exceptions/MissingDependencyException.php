<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a module's required dependencies are missing or not installed
 *
 * Used by ModuleLifecycleService to indicate that a module cannot be installed or enabled
 * because one or more of its required dependencies are not available, not installed,
 * or not enabled (depending on the operation being performed).
 */
class MissingDependencyException extends RuntimeException
{
    //
}

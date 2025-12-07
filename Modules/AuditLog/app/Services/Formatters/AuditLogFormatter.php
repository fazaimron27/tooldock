<?php

namespace Modules\AuditLog\Services\Formatters;

use Modules\AuditLog\Services\AuditLogFormattingHelper;

/**
 * Base abstract class for audit log formatters.
 *
 * Provides common functionality for formatting audit log diffs
 * based on event type. Each formatter handles a specific category
 * of events (authentication, user, file, relationship, generic).
 */
abstract class AuditLogFormatter
{
    use AuditLogFormattingHelper;

    /**
     * Format the diff for the given old and new values.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  string|null  $event  Optional event type for formatters that need it
     * @return array<string>
     */
    abstract public function format(array $oldValues, array $newValues, ?string $event = null): array;
}

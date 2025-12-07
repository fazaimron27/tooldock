<?php

namespace Modules\AuditLog\Services;

use Modules\AuditLog\Services\Formatters\AuthenticationEventFormatter;
use Modules\AuditLog\Services\Formatters\FileEventFormatter;
use Modules\AuditLog\Services\Formatters\GenericEventFormatter;
use Modules\AuditLog\Services\Formatters\RelationshipEventFormatter;
use Modules\AuditLog\Services\Formatters\UserEventFormatter;

/**
 * Factory for creating appropriate audit log formatters based on event type.
 *
 * Maps event types to their corresponding formatter classes,
 * ensuring the correct formatter is used for each event category.
 */
class AuditLogFormatterFactory
{
    /**
     * Get the appropriate formatter for the given event type.
     *
     * @param  string  $event
     * @return AuthenticationEventFormatter|FileEventFormatter|GenericEventFormatter|RelationshipEventFormatter|UserEventFormatter
     */
    public static function make(string $event): AuthenticationEventFormatter|FileEventFormatter|GenericEventFormatter|RelationshipEventFormatter|UserEventFormatter
    {
        return match (true) {
            in_array($event, ['registered', 'login', 'logout', 'password_reset', 'password_changed', 'password_reset_requested'], true) => new AuthenticationEventFormatter,
            in_array($event, ['email_verified', 'email_changed', 'account_deleted'], true) => new UserEventFormatter,
            in_array($event, ['file_uploaded', 'file_deleted'], true) => new FileEventFormatter,
            $event === 'relationship_synced' => new RelationshipEventFormatter,
            default => new GenericEventFormatter,
        };
    }
}

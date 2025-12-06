<?php

namespace Modules\AuditLog\App\Enums;

/**
 * Audit log event type constants.
 *
 * Centralized location for all audit log event names to prevent magic strings
 * and ensure consistency across the application.
 */
class AuditLogEvent
{
    // Standard CRUD events
    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const DELETED = 'deleted';

    // Authentication events
    public const LOGIN = 'login';

    public const LOGOUT = 'logout';

    public const REGISTERED = 'registered';

    public const PASSWORD_RESET = 'password_reset';

    public const PASSWORD_RESET_REQUESTED = 'password_reset_requested';

    public const PASSWORD_CHANGED = 'password_changed';

    // Email events
    public const EMAIL_VERIFIED = 'email_verified';

    public const EMAIL_CHANGED = 'email_changed';

    // Account events
    public const ACCOUNT_DELETED = 'account_deleted';

    // Media events
    public const FILE_UPLOADED = 'file_uploaded';

    public const FILE_DELETED = 'file_deleted';

    // Relationship events
    public const RELATIONSHIP_SYNCED = 'relationship_synced';

    // System events
    public const EXPORT = 'export';

    /**
     * Get all events that don't require model existence check.
     *
     * @return array<string>
     */
    public static function eventsWithoutModelCheck(): array
    {
        return [
            self::DELETED,
            self::FILE_DELETED,
            self::LOGIN,
            self::LOGOUT,
            self::PASSWORD_RESET,
            self::PASSWORD_RESET_REQUESTED,
            self::EMAIL_VERIFIED,
            self::EXPORT,
        ];
    }

    /**
     * Get all events that should set model to null in job constructor.
     *
     * @return array<string>
     */
    public static function eventsWithNullModel(): array
    {
        return [
            self::DELETED,
            self::FILE_DELETED,
        ];
    }
}

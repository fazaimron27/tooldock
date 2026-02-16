<?php

/**
 * Audit Log Event Enum.
 *
 * Defines constants for all audit log event types and provides helper
 * methods for event categorization, icon mapping, and color assignment.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\AuditLog\Enums;

/**
 * Audit log event type constants.
 *
 * Centralized location for all audit log event names to prevent magic strings
 * and ensure consistency across the application.
 */
class AuditLogEvent
{
    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const DELETED = 'deleted';

    public const LOGIN = 'login';

    public const LOGOUT = 'logout';

    public const REGISTERED = 'registered';

    public const PASSWORD_RESET = 'password_reset';

    public const PASSWORD_RESET_REQUESTED = 'password_reset_requested';

    public const PASSWORD_CHANGED = 'password_changed';

    public const EMAIL_VERIFIED = 'email_verified';

    public const EMAIL_CHANGED = 'email_changed';

    public const ACCOUNT_DELETED = 'account_deleted';

    public const FILE_UPLOADED = 'file_uploaded';

    public const FILE_DELETED = 'file_deleted';

    public const RELATIONSHIP_SYNCED = 'relationship_synced';

    public const EXPORT = 'export';

    /**
     * Get all events that don't require model existence check.
     *
     * @return array<string> Event names that skip model existence validation
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
     * @return array<string> Event names where the model is nullified before dispatch
     */
    public static function eventsWithNullModel(): array
    {
        return [
            self::DELETED,
            self::FILE_DELETED,
        ];
    }

    /**
     * Get all valid event types.
     *
     * @return array<string> All recognized audit log event names
     */
    public static function allEvents(): array
    {
        return [
            self::CREATED,
            self::UPDATED,
            self::DELETED,
            self::LOGIN,
            self::LOGOUT,
            self::REGISTERED,
            self::PASSWORD_RESET,
            self::PASSWORD_RESET_REQUESTED,
            self::PASSWORD_CHANGED,
            self::EMAIL_VERIFIED,
            self::EMAIL_CHANGED,
            self::ACCOUNT_DELETED,
            self::FILE_UPLOADED,
            self::FILE_DELETED,
            self::RELATIONSHIP_SYNCED,
            self::EXPORT,
        ];
    }

    /**
     * Get icon name for an event.
     *
     * @param  string  $event  Event name
     * @return string Icon name
     */
    public static function getIcon(string $event): string
    {
        return match ($event) {
            self::CREATED, self::REGISTERED => 'Plus',
            self::UPDATED => 'Edit',
            self::DELETED, self::ACCOUNT_DELETED => 'Trash',
            self::LOGIN => 'LogIn',
            self::LOGOUT => 'LogOut',
            self::PASSWORD_RESET,
            self::PASSWORD_RESET_REQUESTED,
            self::PASSWORD_CHANGED => 'Key',
            self::EMAIL_VERIFIED,
            self::EMAIL_CHANGED => 'Mail',
            self::FILE_UPLOADED => 'Upload',
            self::FILE_DELETED => 'FileX',
            self::RELATIONSHIP_SYNCED => 'Link',
            self::EXPORT => 'Download',
            default => 'Activity',
        };
    }

    /**
     * Get color class for an event.
     *
     * @param  string  $event  Event name
     * @return string Color class
     */
    public static function getColor(string $event): string
    {
        return match ($event) {
            self::CREATED, self::REGISTERED => 'bg-green-500',
            self::UPDATED => 'bg-blue-500',
            self::DELETED, self::ACCOUNT_DELETED => 'bg-red-500',
            self::LOGIN => 'bg-indigo-500',
            self::LOGOUT => 'bg-amber-500',
            self::PASSWORD_RESET,
            self::PASSWORD_RESET_REQUESTED,
            self::PASSWORD_CHANGED => 'bg-purple-500',
            self::EMAIL_VERIFIED,
            self::EMAIL_CHANGED => 'bg-cyan-500',
            self::FILE_UPLOADED => 'bg-emerald-500',
            self::FILE_DELETED => 'bg-orange-500',
            self::RELATIONSHIP_SYNCED => 'bg-pink-500',
            self::EXPORT => 'bg-teal-500',
            default => 'bg-gray-800',
        };
    }
}

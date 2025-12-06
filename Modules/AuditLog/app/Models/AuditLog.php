<?php

namespace Modules\AuditLog\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Core\App\Models\User;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * The data type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Cache tag name for audit log-related cache entries.
     * Used for efficient bulk invalidation via Redis tags.
     */
    private const CACHE_TAG = 'auditlog';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get tags as an array.
     *
     * @return Attribute
     */
    protected function tagsArray(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? array_filter(explode(',', $value)) : []
        );
    }

    /**
     * Get a human-readable formatted diff of old_values vs new_values.
     *
     * @return Attribute
     */
    protected function formattedDiff(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                // Ensure values are arrays (handle JSON strings or null values)
                // Use casts if available, otherwise fall back to ensureArray
                $oldValues = is_array($attributes['old_values'] ?? null)
                    ? ($attributes['old_values'] ?? [])
                    : $this->ensureArray($attributes['old_values'] ?? []);
                $newValues = is_array($attributes['new_values'] ?? null)
                    ? ($attributes['new_values'] ?? [])
                    : $this->ensureArray($attributes['new_values'] ?? []);
                $event = $attributes['event'] ?? 'updated';

                return match ($event) {
                    'created' => $this->formatCreatedDiff($newValues),
                    'deleted' => $this->formatDeletedDiff($oldValues),
                    'registered' => $this->formatRegisteredDiff($newValues),
                    'login' => $this->formatLoginDiff($newValues),
                    'logout' => $this->formatLogoutDiff($oldValues),
                    'password_reset' => $this->formatPasswordResetDiff($newValues),
                    'password_changed' => $this->formatPasswordChangedDiff($newValues),
                    'password_reset_requested' => $this->formatPasswordResetRequestedDiff($newValues),
                    'email_verified' => $this->formatEmailVerifiedDiff($newValues),
                    'email_changed' => $this->formatEmailChangedDiff($oldValues, $newValues),
                    'account_deleted' => $this->formatAccountDeletedDiff($oldValues),
                    'export' => $this->formatExportDiff($newValues),
                    'file_uploaded' => $this->formatFileUploadedDiff($newValues),
                    'file_deleted' => $this->formatFileDeletedDiff($oldValues),
                    'relationship_synced' => $this->formatRelationshipSyncedDiff($oldValues, $newValues),
                    default => $this->formatUpdatedDiff($oldValues, $newValues),
                };
            }
        );
    }

    /**
     * Ensure a value is an array.
     *
     * Handles cases where the value might be a JSON string or null.
     *
     * @param  mixed  $value
     * @return array<string, mixed>
     */
    protected function ensureArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Format diff for created events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatCreatedDiff(array $newValues): array
    {
        $changes = [];

        foreach ($newValues as $key => $value) {
            $fieldName = $this->formatFieldName($key);
            $formattedValue = $this->formatValue($value);

            if ($formattedValue !== null) {
                $changes[] = "Added {$fieldName}: {$formattedValue}";
            } else {
                $changes[] = "Added {$fieldName}";
            }
        }

        return $changes;
    }

    /**
     * Format diff for deleted events.
     *
     * @param  array<string, mixed>  $oldValues
     * @return array<string>
     */
    protected function formatDeletedDiff(array $oldValues): array
    {
        $changes = [];

        foreach ($oldValues as $key => $value) {
            $fieldName = $this->formatFieldName($key);
            $formattedValue = $this->formatValue($value);

            if ($formattedValue !== null) {
                $changes[] = "Removed {$fieldName}: {$formattedValue}";
            } else {
                $changes[] = "Removed {$fieldName}";
            }
        }

        return $changes;
    }

    /**
     * Format diff for updated events.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatUpdatedDiff(array $oldValues, array $newValues): array
    {
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            // Skip if values are the same
            if ($oldValue === $newValue) {
                continue;
            }

            $fieldName = $this->formatFieldName($key);
            $formattedOldValue = $this->formatValue($oldValue);
            $formattedNewValue = $this->formatValue($newValue);

            if ($formattedOldValue === null && $formattedNewValue !== null) {
                $changes[] = "Set {$fieldName} to {$formattedNewValue}";
            } elseif ($formattedOldValue !== null && $formattedNewValue === null) {
                $changes[] = "Removed {$fieldName} (was {$formattedOldValue})";
            } else {
                $changes[] = "Changed {$fieldName} from {$formattedOldValue} to {$formattedNewValue}";
            }
        }

        return $changes;
    }

    /**
     * Format diff for registered events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatRegisteredDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $name = $newValues['name'] ?? null;

        if ($email && $name) {
            $changes[] = "User {$name} ({$email}) registered";
        } elseif ($email) {
            $changes[] = "User {$email} registered";
        } elseif ($name) {
            $changes[] = "User {$name} registered";
        } else {
            $changes[] = 'New user registered';
        }

        return $changes;
    }

    /**
     * Format diff for login events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatLoginDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $loggedInAt = $newValues['logged_in_at'] ?? null;

        if ($email) {
            $changes[] = "User {$email} logged in";
        } else {
            $changes[] = 'User logged in';
        }

        if ($loggedInAt) {
            try {
                $date = \Carbon\Carbon::parse($loggedInAt);
                $changes[] = "Login time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for logout events.
     *
     * @param  array<string, mixed>  $oldValues
     * @return array<string>
     */
    protected function formatLogoutDiff(array $oldValues): array
    {
        $changes = [];
        $email = $oldValues['email'] ?? null;
        $loggedOutAt = $oldValues['logged_out_at'] ?? null;

        if ($email) {
            $changes[] = "User {$email} logged out";
        } else {
            $changes[] = 'User logged out';
        }

        if ($loggedOutAt) {
            try {
                $date = \Carbon\Carbon::parse($loggedOutAt);
                $changes[] = "Logout time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for password reset events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatPasswordResetDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $resetAt = $newValues['reset_at'] ?? null;

        if ($email) {
            $changes[] = "Password reset for user {$email}";
        } else {
            $changes[] = 'Password reset';
        }

        if ($resetAt) {
            try {
                $date = Carbon::parse($resetAt);
                $changes[] = "Reset time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for password changed events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatPasswordChangedDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $changedAt = $newValues['changed_at'] ?? null;

        if ($email) {
            $changes[] = "Password changed for user {$email}";
        } else {
            $changes[] = 'Password changed';
        }

        if ($changedAt) {
            try {
                $date = Carbon::parse($changedAt);
                $changes[] = "Change time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for password reset requested events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatPasswordResetRequestedDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $requestedAt = $newValues['requested_at'] ?? null;

        if ($email) {
            $changes[] = "Password reset requested for {$email}";
        } else {
            $changes[] = 'Password reset requested';
        }

        if ($requestedAt) {
            try {
                $date = Carbon::parse($requestedAt);
                $changes[] = "Request time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for email verified events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatEmailVerifiedDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $verifiedAt = $newValues['verified_at'] ?? null;

        if ($email) {
            $changes[] = "Email {$email} verified";
        } else {
            $changes[] = 'Email verified';
        }

        if ($verifiedAt) {
            try {
                $date = Carbon::parse($verifiedAt);
                $changes[] = "Verification time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for email changed events.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatEmailChangedDiff(array $oldValues, array $newValues): array
    {
        $changes = [];
        $oldEmail = $oldValues['email'] ?? null;
        $newEmail = $newValues['email'] ?? null;
        $changedAt = $newValues['changed_at'] ?? null;

        if ($oldEmail && $newEmail) {
            $changes[] = "Email changed from {$oldEmail} to {$newEmail}";
        } elseif ($newEmail) {
            $changes[] = "Email set to {$newEmail}";
        } elseif ($oldEmail) {
            $changes[] = "Email {$oldEmail} removed";
        } else {
            $changes[] = 'Email changed';
        }

        if ($changedAt) {
            try {
                $date = Carbon::parse($changedAt);
                $changes[] = "Change time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for account deleted events.
     *
     * @param  array<string, mixed>  $oldValues
     * @return array<string>
     */
    protected function formatAccountDeletedDiff(array $oldValues): array
    {
        $changes = [];
        $email = $oldValues['email'] ?? null;
        $name = $oldValues['name'] ?? null;
        $deletedAt = $oldValues['deleted_at'] ?? null;

        if ($email && $name) {
            $changes[] = "Account deleted for user {$name} ({$email})";
        } elseif ($email) {
            $changes[] = "Account deleted for user {$email}";
        } elseif ($name) {
            $changes[] = "Account deleted for user {$name}";
        } else {
            $changes[] = 'Account deleted';
        }

        if ($deletedAt) {
            try {
                $date = Carbon::parse($deletedAt);
                $changes[] = "Deletion time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for export events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatExportDiff(array $newValues): array
    {
        $changes = [];
        $format = $newValues['format'] ?? 'CSV';
        $recordCount = $newValues['record_count'] ?? null;
        $exportedAt = $newValues['exported_at'] ?? null;

        $changes[] = "Exported audit logs as {$format}";

        if ($recordCount !== null) {
            $changes[] = "Exported {$recordCount} record".($recordCount === 1 ? '' : 's');
        }

        if ($exportedAt) {
            try {
                $date = Carbon::parse($exportedAt);
                $changes[] = "Export time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for file uploaded events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatFileUploadedDiff(array $newValues): array
    {
        $changes = [];
        $filename = $newValues['filename'] ?? null;
        $mimeType = $newValues['mime_type'] ?? null;
        $size = $newValues['size'] ?? null;
        $isTemporary = $newValues['is_temporary'] ?? false;
        $uploadedAt = $newValues['created_at'] ?? null;

        if ($filename) {
            $fileType = $isTemporary ? 'temporary' : 'permanent';
            $changes[] = "File '{$filename}' uploaded ({$fileType})";
        } else {
            $changes[] = 'File uploaded';
        }

        if ($mimeType) {
            $changes[] = "MIME type: {$mimeType}";
        }

        if ($size !== null) {
            $formattedSize = $this->formatFileSize($size);
            $changes[] = "Size: {$formattedSize}";
        }

        if ($uploadedAt) {
            try {
                $date = Carbon::parse($uploadedAt);
                $changes[] = "Uploaded at: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for file deleted events.
     *
     * @param  array<string, mixed>  $oldValues
     * @return array<string>
     */
    protected function formatFileDeletedDiff(array $oldValues): array
    {
        $changes = [];
        $filename = $oldValues['filename'] ?? null;
        $mimeType = $oldValues['mime_type'] ?? null;
        $size = $oldValues['size'] ?? null;
        $deletedAt = $oldValues['deleted_at'] ?? now()->toIso8601String();

        if ($filename) {
            $changes[] = "File '{$filename}' deleted";
        } else {
            $changes[] = 'File deleted';
        }

        if ($mimeType) {
            $changes[] = "MIME type: {$mimeType}";
        }

        if ($size !== null) {
            $formattedSize = $this->formatFileSize($size);
            $changes[] = "Size: {$formattedSize}";
        }

        if ($deletedAt) {
            try {
                $date = Carbon::parse($deletedAt);
                $changes[] = "Deleted at: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception $e) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for relationship synced events.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatRelationshipSyncedDiff(array $oldValues, array $newValues): array
    {
        $changes = [];

        // Find relationship name from keys (e.g., 'roles', 'permissions', 'members')
        $relationshipKeys = array_filter(
            array_keys($newValues),
            fn ($key) => ! str_ends_with($key, '_ids')
        );

        foreach ($relationshipKeys as $relationshipName) {
            $oldNames = $oldValues[$relationshipName] ?? [];
            $newNames = $newValues[$relationshipName] ?? [];
            $oldIds = $oldValues[$relationshipName.'_ids'] ?? [];
            $newIds = $newValues[$relationshipName.'_ids'] ?? [];

            if ($oldIds === $newIds) {
                continue;
            }

            $relationshipDisplayName = ucwords(str_replace(['_', '-'], ' ', $relationshipName));

            $added = array_diff($newIds, $oldIds);
            $removed = array_diff($oldIds, $newIds);

            if (! empty($added)) {
                $addedNames = array_intersect_key($newNames, array_flip($added));
                if (! empty($addedNames)) {
                    $changes[] = "Added {$relationshipDisplayName}: ".implode(', ', $addedNames);
                } else {
                    $changes[] = "Added {$relationshipDisplayName}: ".count($added).' item(s)';
                }
            }

            if (! empty($removed)) {
                $removedNames = array_intersect_key($oldNames, array_flip($removed));
                if (! empty($removedNames)) {
                    $changes[] = "Removed {$relationshipDisplayName}: ".implode(', ', $removedNames);
                } else {
                    $changes[] = "Removed {$relationshipDisplayName}: ".count($removed).' item(s)';
                }
            }
        }

        if (empty($changes)) {
            $changes[] = 'Relationship synchronized';
        }

        return $changes;
    }

    /**
     * Format file size in human-readable format.
     *
     * @param  int  $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Format a field name for human readability.
     *
     * @param  string  $key
     * @return string
     */
    protected function formatFieldName(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Format a value for human-readable display.
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function formatValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('F j, Y \a\t g:i A');
        }

        $stringValue = (string) $value;

        // Check if string is a date and format it
        if ($this->isDateString($stringValue)) {
            try {
                $date = Carbon::parse($stringValue);

                return $date->format('F j, Y \a\t g:i A');
            } catch (\Exception $e) {
                // Fall through to default string formatting if parsing fails
            }
        }

        // Truncate very long values
        if (strlen($stringValue) > 100) {
            return substr($stringValue, 0, 100).'...';
        }

        return "'{$stringValue}'";
    }

    /**
     * Check if a string value matches a date pattern.
     *
     * Uses Carbon to attempt parsing, which is more reliable than regex patterns.
     * Carbon can handle many date formats including ISO, MySQL datetime, and more.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isDateString(string $value): bool
    {
        // Skip obviously non-date strings (too short or too long)
        $length = strlen($value);
        if ($length < 8 || $length > 50) {
            return false;
        }

        // Skip strings that don't contain date-like characters (at least 4 digits for year)
        if (! preg_match('/\d{4}/', $value)) {
            return false;
        }

        try {
            // Try to parse with Carbon - if it succeeds, it's likely a date
            $date = Carbon::parse($value);

            // Additional validation: ensure it's a reasonable date
            // Reject dates that are clearly not valid (e.g., year 0000 or year 9999)
            $year = $date->year;

            return $year >= 1000 && $year <= 9999;
        } catch (\Exception $e) {
            // If Carbon can't parse it, it's not a date
            return false;
        }
    }

    /**
     * Get human-readable causer information.
     *
     * @return Attribute
     */
    protected function causerParams(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $userId = $attributes['user_id'] ?? null;

                if (! $userId) {
                    return app()->runningInConsole() ? 'Console' : 'System';
                }

                try {
                    $user = $this->user;

                    if (! $user) {
                        return "User ID: {$userId}";
                    }

                    // Try to get a readable name (name, email)
                    $name = $user->name ?? $user->email ?? null;

                    if ($name) {
                        return "User: {$name}";
                    }

                    return "User ID: {$userId}";
                } catch (\Throwable) {
                    return "User ID: {$userId}";
                }
            }
        );
    }

    /**
     * Normalize user agent string.
     *
     * Returns null if user agent is "Symfony" (default when no User-Agent header is present).
     *
     * @param  string|null  $userAgent
     * @return string|null
     */
    public static function normalizeUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === 'Symfony') {
            return null;
        }

        return $userAgent;
    }

    /**
     * Get the parent auditable model.
     *
     * Handles cases where the model class or table no longer exists
     * (e.g., when modules are uninstalled).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Request-level cache for table existence checks.
     *
     * @var array<string, bool>
     */
    private static array $tableExistenceCache = [];

    /**
     * Request-level cache for table names.
     *
     * @var array<string, string|null>
     */
    private static array $tableNameCache = [];

    /**
     * Check if the auditable model class exists and its table is available.
     *
     * @return bool True if the model class exists and its table exists, false otherwise
     */
    public function auditableExists(): bool
    {
        if (! $this->auditable_type) {
            return false;
        }

        return self::isAuditableTypeValid($this->auditable_type);
    }

    /**
     * Check if an auditable type (model class) is valid and its table exists.
     *
     * Uses request-level caching to avoid repeated checks.
     *
     * @param  string  $type  The fully qualified model class name
     * @return bool True if the model class exists and its table exists, false otherwise
     */
    public static function isAuditableTypeValid(string $type): bool
    {
        if (isset(self::$tableExistenceCache[$type])) {
            return self::$tableExistenceCache[$type];
        }

        if (! class_exists($type)) {
            self::$tableExistenceCache[$type] = false;

            return false;
        }

        $table = self::getTableNameForModel($type);

        if ($table === null) {
            self::$tableExistenceCache[$type] = false;

            return false;
        }

        try {
            $exists = Schema::hasTable($table);
            self::$tableExistenceCache[$type] = $exists;

            return $exists;
        } catch (\Exception) {
            self::$tableExistenceCache[$type] = false;

            return false;
        }
    }

    /**
     * Get the table name for a model class.
     *
     * Uses request-level caching. Attempts to use a static method if available,
     * otherwise instantiates the model to get the table name via Eloquent's getTable() method.
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @return string|null The table name, or null if the class doesn't exist or can't be determined
     */
    public static function getTableNameForModel(string $modelClass): ?string
    {
        if (isset(self::$tableNameCache[$modelClass])) {
            return self::$tableNameCache[$modelClass];
        }

        if (! class_exists($modelClass)) {
            self::$tableNameCache[$modelClass] = null;

            return null;
        }

        try {
            $reflection = new \ReflectionClass($modelClass);

            if ($reflection->hasMethod('getTableName') && $reflection->getMethod('getTableName')->isStatic()) {
                $tableName = $modelClass::getTableName();
                self::$tableNameCache[$modelClass] = $tableName;

                return $tableName;
            }

            $constructor = $reflection->getConstructor();
            if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
                self::$tableNameCache[$modelClass] = null;

                return null;
            }

            $model = new $modelClass;
            $tableName = $model->getTable();
            self::$tableNameCache[$modelClass] = $tableName;

            return $tableName;
        } catch (\Exception) {
            self::$tableNameCache[$modelClass] = null;

            return null;
        }
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * Converts timestamps to the application timezone when serializing
     * to ensure frontend receives dates in local timezone.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return Carbon::instance($date)
            ->setTimezone(config('app.timezone'))
            ->toIso8601String();
    }

    /**
     * Invalidate the cached model types list.
     *
     * Optimized for Redis - uses tag-based flush for efficient invalidation.
     * Call this method when you know new model types have been added
     * to ensure the filter dropdown is up-to-date.
     */
    public static function invalidateModelTypesCache(): void
    {
        try {
            Cache::tags([self::CACHE_TAG])->flush();

            Log::debug('AuditLog: Audit log cache cleared via Redis tags', [
                'tag' => self::CACHE_TAG,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditLog: Failed to clear cache', [
                'tag' => self::CACHE_TAG,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Create an audit log entry directly without queuing.
     *
     * Useful for bulk operations where queuing would be inefficient.
     * This method bypasses the CreateAuditLogJob queue and inserts directly.
     *
     * @param  string  $event  The event type ('created', 'updated', 'deleted')
     * @param  Model  $model  The model being audited
     * @param  array|null  $oldValues  Old values (for updated/deleted events)
     * @param  array|null  $newValues  New values (for created/updated events)
     * @param  string|null  $userId  The user ID performing the action
     * @param  string|null  $url  The request URL
     * @param  string|null  $ipAddress  The request IP address
     * @param  string|null  $userAgent  The request user agent
     * @return self
     */
    public static function createDirect(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $userId = null,
        ?string $url = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $tags = null
    ): self {
        return self::create([
            'user_id' => $userId ?? Auth::id(),
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $url ?? request()?->url(),
            'ip_address' => $ipAddress ?? request()?->ip(),
            'user_agent' => $userAgent ?? self::normalizeUserAgent(request()?->userAgent()),
            'tags' => $tags,
        ]);
    }

    /**
     * Bulk insert audit logs directly without queuing.
     *
     * Much faster than individual creates for bulk operations.
     * Uses raw insert() for maximum performance.
     *
     * @param  array<int, array{event: string, model: Model, oldValues?: array|null, newValues?: array|null, userId?: string|null, url?: string|null, ipAddress?: string|null, userAgent?: string|null, tags?: string|array|null}>  $entries  Array of audit log entries
     * @return void
     */
    public static function bulkInsert(array $entries): void
    {
        if (empty($entries)) {
            return;
        }

        $auditLogs = [];
        $now = now();

        foreach ($entries as $entry) {
            $model = $entry['model'];
            /**
             * JSON encode arrays manually since raw insert() bypasses Eloquent casts.
             * Eloquent's create() would handle this automatically, but bulk insert requires manual encoding.
             */
            $auditLogs[] = [
                'user_id' => $entry['userId'] ?? Auth::id(),
                'event' => $entry['event'],
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'old_values' => $entry['oldValues'] !== null ? json_encode($entry['oldValues']) : null,
                'new_values' => $entry['newValues'] !== null ? json_encode($entry['newValues']) : null,
                'url' => $entry['url'] ?? request()?->url(),
                'ip_address' => $entry['ipAddress'] ?? request()?->ip(),
                'user_agent' => $entry['userAgent'] ?? self::normalizeUserAgent(request()?->userAgent()),
                'tags' => isset($entry['tags']) && is_array($entry['tags']) ? implode(',', $entry['tags']) : ($entry['tags'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        self::insert($auditLogs);
    }
}

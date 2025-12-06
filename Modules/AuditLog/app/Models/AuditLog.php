<?php

namespace Modules\AuditLog\App\Models;

use Carbon\Carbon;
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
        ?string $userAgent = null
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
            'user_agent' => $userAgent ?? request()?->userAgent(),
        ]);
    }

    /**
     * Bulk insert audit logs directly without queuing.
     *
     * Much faster than individual creates for bulk operations.
     * Uses raw insert() for maximum performance.
     *
     * @param  array<int, array{event: string, model: Model, oldValues?: array|null, newValues?: array|null, userId?: string|null, url?: string|null, ipAddress?: string|null, userAgent?: string|null}>  $entries  Array of audit log entries
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
                'user_agent' => $entry['userAgent'] ?? request()?->userAgent(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        self::insert($auditLogs);
    }
}

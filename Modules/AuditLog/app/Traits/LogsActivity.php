<?php

namespace Modules\AuditLog\Traits;

use App\Services\Cache\CacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\Enums\AuditLogEvent;
use Modules\AuditLog\Jobs\CreateAuditLogJob;

trait LogsActivity
{
    /**
     * Indicates if the model should log activity.
     *
     * @var bool
     */
    protected static $logActivity = true;

    /**
     * Cached CacheService instance to avoid repeated container lookups.
     *
     * @var CacheService|null
     */
    private static ?CacheService $cacheService = null;

    /**
     * Boot the LogsActivity trait.
     */
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            if (! static::shouldLogActivity($model)) {
                return;
            }

            $attributes = static::processSensitiveFields($model->getAttributes(), $model);
            $tagsString = static::getAuditTagsString($model);

            static::dispatchAuditLog(
                event: AuditLogEvent::CREATED,
                model: $model,
                oldValues: null,
                newValues: $attributes,
                tags: $tagsString
            );
        });

        static::updated(function (Model $model) {
            if (! static::shouldLogActivity($model)) {
                return;
            }

            $dirty = $model->getDirty();

            if (empty($dirty)) {
                return;
            }

            $oldValues = [];
            $original = $model->getOriginal();

            foreach ($dirty as $key => $value) {
                if (static::isExcludedField($key, $model)) {
                    continue;
                }
                $oldValues[$key] = $original[$key] ?? null;
            }

            $processedDirty = static::processSensitiveFields($dirty, $model);
            $processedOldValues = static::processSensitiveFields($oldValues, $model);

            if (empty($processedDirty)) {
                return;
            }

            $tagsString = static::getAuditTagsString($model);

            static::dispatchAuditLog(
                event: AuditLogEvent::UPDATED,
                model: $model,
                oldValues: $processedOldValues,
                newValues: $processedDirty,
                tags: $tagsString
            );
        });

        static::deleted(function (Model $model) {
            if (! static::shouldLogActivity($model)) {
                return;
            }

            $attributes = static::processSensitiveFields($model->getAttributes(), $model);
            $tagsString = static::getAuditTagsString($model);

            static::dispatchAuditLog(
                event: AuditLogEvent::DELETED,
                model: $model,
                oldValues: $attributes,
                newValues: null,
                tags: $tagsString
            );
        });
    }

    /**
     * Get audit tags as a comma-separated string.
     *
     * Collects tags from:
     * 1. Model's $auditTags property (if exists)
     * 2. Model's getAuditTags() method (if exists and not from trait)
     *
     * Uses persistent caching via CacheService to optimize reflection operations.
     *
     * @param  Model  $model
     * @return string|null
     */
    protected static function getAuditTagsString(Model $model): ?string
    {
        $tags = static::collectAuditTags($model);

        return ! empty($tags) ? implode(',', $tags) : null;
    }

    /**
     * Get audit tags array from the model.
     *
     * Collects tags from:
     * 1. Model's $auditTags property (if exists)
     * 2. Model's getAuditTags() method (if exists and not from trait)
     *
     * Uses persistent caching via CacheService to optimize reflection operations.
     *
     * @param  Model  $model
     * @return array<string>
     */
    protected static function collectAuditTags(Model $model): array
    {
        $tags = [];

        /**
         * Collect tags from model's $auditTags property if it exists.
         */
        if (property_exists($model, 'auditTags') && is_array($model->auditTags)) {
            $tags = array_merge($tags, $model->auditTags);
        }

        /**
         * Check for getAuditTags() method with reflection caching.
         */
        $modelClass = get_class($model);
        $cacheKey = "audit_log_trait_method_info:{$modelClass}";

        $methodInfo = static::getCacheService()->rememberForever(
            $cacheKey,
            fn () => static::analyzeGetAuditTagsMethod($model),
            'auditlog',
            'LogsActivity'
        );

        if ($methodInfo['hasMethod'] && ! $methodInfo['isStatic']) {
            $tags = array_merge($tags, $model->getAuditTags());
        }

        return array_unique($tags);
    }

    /**
     * Analyze the model's getAuditTags method using reflection.
     *
     * Uses persistent caching via CacheService for the trait file path.
     *
     * @param  Model  $model
     * @return array{hasMethod: bool, isStatic: bool, filePath: string|null}
     */
    protected static function analyzeGetAuditTagsMethod(Model $model): array
    {
        $reflection = new \ReflectionClass($model);

        if (! $reflection->hasMethod('getAuditTags')) {
            return ['hasMethod' => false, 'isStatic' => false, 'filePath' => null];
        }

        $method = $reflection->getMethod('getAuditTags');
        $methodFile = realpath($method->getFileName());
        if ($methodFile === false) {
            $methodFile = null;
        }

        /**
         * Cache trait file path to detect if method is from trait or model.
         */
        $traitFilePath = static::getCacheService()->rememberForever(
            'audit_log_trait_file_path',
            fn () => realpath(__FILE__) ?: __FILE__,
            'auditlog',
            'LogsActivity'
        );

        /**
         * Only use model's method if it's defined in the model, not in the trait.
         */
        if ($methodFile !== null && $methodFile !== $traitFilePath) {
            return [
                'hasMethod' => true,
                'isStatic' => $method->isStatic(),
                'filePath' => $methodFile,
            ];
        }

        return ['hasMethod' => false, 'isStatic' => false, 'filePath' => null];
    }

    /**
     * Get the CacheService instance.
     *
     * Resolves from container since traits can't use dependency injection.
     * Caches the instance to avoid repeated container lookups.
     *
     * @return CacheService
     */
    protected static function getCacheService(): CacheService
    {
        if (static::$cacheService === null) {
            static::$cacheService = App::make(CacheService::class);
        }

        return static::$cacheService;
    }

    /**
     * Invalidate all trait-related cache entries.
     *
     * Flushes all cache entries tagged with 'auditlog', including:
     * - Method reflection cache for all models
     * - Trait file path cache
     *
     * Useful when models are modified or code is deployed.
     *
     * @return bool True if cache was successfully invalidated
     */
    public static function invalidateCache(): bool
    {
        return static::getCacheService()->flush('auditlog', 'LogsActivity');
    }

    /**
     * Invalidate cache for a specific model class.
     *
     * Removes the cached method reflection information for the given model class.
     * Useful when a model's getAuditTags() method is modified.
     *
     * @param  string  $modelClass  Fully qualified model class name
     * @return bool True if cache was successfully invalidated
     */
    public static function invalidateModelCache(string $modelClass): bool
    {
        $cacheKey = "audit_log_trait_method_info:{$modelClass}";

        return static::getCacheService()->forget($cacheKey, 'auditlog', 'LogsActivity');
    }

    /**
     * Clear the cached CacheService instance.
     *
     * Forces the next call to getCacheService() to resolve a fresh instance.
     * Useful for testing or when CacheService configuration changes.
     *
     * @return void
     */
    public static function clearCacheServiceInstance(): void
    {
        static::$cacheService = null;
    }

    /**
     * Determine if activity should be logged for the model.
     */
    protected static function shouldLogActivity(Model $model): bool
    {
        if (isset($model->logActivity) && $model->logActivity === false) {
            return false;
        }

        if (static::$logActivity === false) {
            return false;
        }

        return true;
    }

    /**
     * Disable activity logging for the current model instance.
     *
     * @return $this
     */
    public function withoutLogging(): self
    {
        $this->logActivity = false;

        return $this;
    }

    /**
     * Enable activity logging for the current model instance.
     *
     * @return $this
     */
    public function withLogging(): self
    {
        $this->logActivity = true;

        return $this;
    }

    /**
     * Execute a callback without logging activity.
     *
     * @param  callable  $callback
     * @return mixed
     */
    public static function withoutLoggingActivity(callable $callback): mixed
    {
        $original = static::$logActivity;
        static::$logActivity = false;

        try {
            return $callback();
        } finally {
            static::$logActivity = $original;
        }
    }

    /**
     * Get the current user ID, handling console contexts.
     */
    protected static function getUserId(): ?string
    {
        if (app()->runningInConsole()) {
            return null;
        }

        return Auth::id();
    }

    /**
     * Get the current request URL, handling console contexts.
     */
    protected static function getRequestUrl(): ?string
    {
        if (app()->runningInConsole() || ! request()) {
            return null;
        }

        return request()->url();
    }

    /**
     * Get the current request IP address, handling console contexts.
     */
    protected static function getRequestIp(): ?string
    {
        if (app()->runningInConsole() || ! request()) {
            return null;
        }

        return request()->ip();
    }

    /**
     * Get the current user agent, handling console contexts.
     *
     * Returns null if:
     * - Running in console
     * - No request available
     * - User agent is "Symfony" (default when no User-Agent header is present)
     *
     * Uses AuditLog::normalizeUserAgent() for consistency.
     */
    protected static function getUserAgent(): ?string
    {
        if (app()->runningInConsole() || ! request()) {
            return null;
        }

        return \Modules\AuditLog\Models\AuditLog::normalizeUserAgent(request()->userAgent());
    }

    /**
     * Dispatch an audit log job with common parameters.
     *
     * Helper method to reduce duplication when dispatching audit log jobs.
     * Automatically includes user ID, URL, IP address, and user agent.
     *
     * @param  string  $event  The audit log event type
     * @param  Model  $model  The model being audited
     * @param  array|null  $oldValues  Old values (for updates/deletes)
     * @param  array|null  $newValues  New values (for creates/updates)
     * @param  string|null  $tags  Optional tags (defaults to model's audit tags)
     * @return void
     */
    protected static function dispatchAuditLog(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $tags = null
    ): void {
        CreateAuditLogJob::dispatch(
            event: $event,
            model: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            userId: static::getUserId(),
            url: static::getRequestUrl(),
            ipAddress: static::getRequestIp(),
            userAgent: static::getUserAgent(),
            tags: $tags ?? static::getAuditTagsString($model)
        );
    }

    /**
     * Get list of fields that should be completely excluded from audit logs.
     *
     * Models can define protected $auditExclude = [] to exclude fields.
     *
     * @param  Model  $model
     * @return array<string>
     */
    protected static function getExcludedFields(Model $model): array
    {
        $excluded = [];

        if (property_exists($model, 'auditExclude') && is_array($model->auditExclude)) {
            $excluded = array_merge($excluded, $model->auditExclude);
        }

        if (method_exists($model, 'getAuditExclude')) {
            $excluded = array_merge($excluded, $model->getAuditExclude());
        }

        return array_unique($excluded);
    }

    /**
     * Get list of sensitive fields that should be redacted (masked) in audit logs.
     *
     * Models can define protected $auditSensitive = [] to redact fields.
     * Default sensitive fields include: password, token, secret, credit_card, etc.
     *
     * @param  Model  $model
     * @return array<string>
     */
    protected static function getSensitiveFields(Model $model): array
    {
        $sensitive = [
            'password',
            'password_confirmation',
            'remember_token',
            'api_token',
            'secret',
            'token',
            'credit_card',
            'credit_card_number',
            'cvv',
            'ssn',
            'social_security_number',
        ];

        if (property_exists($model, 'auditSensitive') && is_array($model->auditSensitive)) {
            $sensitive = array_merge($sensitive, $model->auditSensitive);
        }

        if (method_exists($model, 'getAuditSensitive')) {
            $sensitive = array_merge($sensitive, $model->getAuditSensitive());
        }

        return array_unique($sensitive);
    }

    /**
     * Check if a field should be completely excluded from audit logs.
     *
     * @param  string  $field
     * @param  Model  $model
     * @return bool
     */
    protected static function isExcludedField(string $field, Model $model): bool
    {
        $excludedFields = static::getExcludedFields($model);

        return in_array($field, $excludedFields, true);
    }

    /**
     * Check if a field is sensitive and should be redacted in audit logs.
     *
     * @param  string  $field
     * @param  Model  $model
     * @return bool
     */
    protected static function isSensitiveField(string $field, Model $model): bool
    {
        $sensitiveFields = static::getSensitiveFields($model);

        return in_array($field, $sensitiveFields, true);
    }

    /**
     * Process sensitive fields: exclude excluded fields, redact sensitive fields.
     *
     * This method handles both exclusion (complete removal) and redaction (masking).
     *
     * @param  array<string, mixed>  $attributes
     * @param  Model  $model
     * @return array<string, mixed>
     */
    protected static function processSensitiveFields(array $attributes, Model $model): array
    {
        $processed = [];

        /**
         * Process each attribute:
         * - Skip excluded fields completely
         * - Mask sensitive fields with redaction marker
         * - Keep other fields as-is
         */
        foreach ($attributes as $key => $value) {
            if (static::isExcludedField($key, $model)) {
                continue;
            }

            if (static::isSensitiveField($key, $model)) {
                $processed[$key] = '***REDACTED***';
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }
}

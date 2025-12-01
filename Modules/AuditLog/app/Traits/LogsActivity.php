<?php

namespace Modules\AuditLog\App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\App\Jobs\CreateAuditLogJob;

trait LogsActivity
{
    /**
     * Indicates if the model should log activity.
     *
     * @var bool
     */
    protected static $logActivity = true;

    /**
     * Boot the LogsActivity trait.
     */
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            if (! static::shouldLogActivity($model)) {
                return;
            }

            $attributes = static::filterSensitiveFields($model->getAttributes(), $model);

            CreateAuditLogJob::dispatch(
                event: 'created',
                model: $model,
                oldValues: null,
                newValues: $attributes,
                userId: self::getUserId(),
                url: self::getRequestUrl(),
                ipAddress: self::getRequestIp(),
                userAgent: self::getUserAgent()
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
                if (static::isSensitiveField($key, $model)) {
                    continue;
                }
                $oldValues[$key] = $original[$key] ?? null;
            }

            $filteredDirty = static::filterSensitiveFields($dirty, $model);

            if (empty($filteredDirty)) {
                return;
            }

            CreateAuditLogJob::dispatch(
                event: 'updated',
                model: $model,
                oldValues: $oldValues,
                newValues: $filteredDirty,
                userId: self::getUserId(),
                url: self::getRequestUrl(),
                ipAddress: self::getRequestIp(),
                userAgent: self::getUserAgent()
            );
        });

        static::deleted(function (Model $model) {
            if (! static::shouldLogActivity($model)) {
                return;
            }

            $attributes = static::filterSensitiveFields($model->getAttributes(), $model);

            CreateAuditLogJob::dispatch(
                event: 'deleted',
                model: $model,
                oldValues: $attributes,
                newValues: null,
                userId: self::getUserId(),
                url: self::getRequestUrl(),
                ipAddress: self::getRequestIp(),
                userAgent: self::getUserAgent()
            );
        });
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
    protected static function getUserId(): ?int
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
     */
    protected static function getUserAgent(): ?string
    {
        if (app()->runningInConsole() || ! request()) {
            return null;
        }

        return request()->userAgent();
    }

    /**
     * Get list of sensitive fields that should not be logged.
     *
     * Override this method in your model to exclude additional fields.
     *
     * @return array<string>
     */
    protected static function getSensitiveFields(): array
    {
        return ['password', 'password_confirmation', 'remember_token', 'api_token', 'secret', 'token'];
    }

    /**
     * Check if a field is sensitive and should be excluded from audit logs.
     *
     * @param  string  $field
     * @param  Model  $model
     * @return bool
     */
    protected static function isSensitiveField(string $field, Model $model): bool
    {
        $sensitiveFields = static::getSensitiveFields();

        if (method_exists($model, 'getSensitiveFields')) {
            $sensitiveFields = array_merge($sensitiveFields, $model->getSensitiveFields());
        }

        return in_array($field, $sensitiveFields, true);
    }

    /**
     * Filter sensitive fields from attributes array.
     *
     * @param  array<string, mixed>  $attributes
     * @param  Model  $model
     * @return array<string, mixed>
     */
    protected static function filterSensitiveFields(array $attributes, Model $model): array
    {
        $filtered = [];

        foreach ($attributes as $key => $value) {
            if (! static::isSensitiveField($key, $model)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}

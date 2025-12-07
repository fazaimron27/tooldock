<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Modules\AuditLog\App\Enums\AuditLogEvent;
use Modules\AuditLog\App\Traits\LogsActivity;

class MediaFile extends Model
{
    use HasUuids, LogsActivity;

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

    protected $fillable = [
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
        'model_type',
        'model_id',
        'is_temporary',
    ];

    /**
     * Fields to exclude from audit logging.
     * Administrative fields that don't represent user-facing changes.
     */
    protected $auditExclude = ['is_temporary', 'model_type', 'model_id'];

    /**
     * Timestamp fields that are automatically managed by Eloquent.
     * Used to filter out automatic timestamp changes from audit logs.
     */
    private static array $timestampFields = ['created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'is_temporary' => 'boolean',
            'size' => 'integer',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['url'];

    /**
     * Get the parent model (polymorphic).
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the public URL for the file.
     */
    public function getUrlAttribute(): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk);

        return $disk->url($this->path);
    }

    /**
     * Check if the file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Scope a query to only include temporary files.
     */
    public function scopeTemporary(Builder $query): Builder
    {
        return $query->where('is_temporary', true);
    }

    /**
     * Scope a query to only include permanent files.
     */
    public function scopePermanent(Builder $query): Builder
    {
        return $query->where('is_temporary', false);
    }

    /**
     * Override LogsActivity to use custom event types for file operations.
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
                event: AuditLogEvent::FILE_UPLOADED,
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

            /**
             * Filter out excluded fields and timestamps (automatic system fields).
             */
            $nonExcludedDirty = [];
            foreach ($dirty as $key => $value) {
                if (! static::isExcludedField($key, $model) && ! in_array($key, static::$timestampFields, true)) {
                    $nonExcludedDirty[$key] = $value;
                }
            }

            /**
             * Check if only administrative fields changed (is_temporary, model_type, model_id).
             * These are internal operations, not user-facing changes.
             * If only administrative fields changed, handle temporary to permanent conversion.
             */
            $administrativeFields = property_exists($model, 'auditExclude') && is_array($model->auditExclude)
                ? $model->auditExclude
                : ['is_temporary', 'model_type', 'model_id'];
            $administrativeFieldsFlip = array_flip($administrativeFields);
            $administrativeDirty = array_intersect_key($dirty, $administrativeFieldsFlip);
            $timestampFieldsFlip = array_flip(static::$timestampFields);
            $nonTimestampDirty = array_diff_key($dirty, $timestampFieldsFlip);
            $onlyAdministrativeChanged = ! empty($administrativeDirty) && count($nonTimestampDirty) === count($administrativeDirty);

            if ($onlyAdministrativeChanged) {
                static::handleTemporaryToPermanentConversion($model, $dirty);

                return;
            }

            $oldValues = [];
            $original = $model->getOriginal();

            foreach ($nonExcludedDirty as $key => $value) {
                $oldValues[$key] = $original[$key] ?? null;
            }

            $processedDirty = static::processSensitiveFields($nonExcludedDirty, $model);
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
                event: AuditLogEvent::FILE_DELETED,
                model: $model,
                oldValues: $attributes,
                newValues: null,
                tags: $tagsString
            );
        });
    }

    /**
     * Delete the file from storage when the model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (MediaFile $mediaFile) {
            if (Storage::disk($mediaFile->disk)->exists($mediaFile->path)) {
                Storage::disk($mediaFile->disk)->delete($mediaFile->path);
            }
        });
    }

    /**
     * Handle conversion from temporary to permanent file.
     *
     * Logs a file_uploaded event when a file is converted from temporary to permanent.
     *
     * @param  Model  $model  The media file model
     * @param  array  $dirty  The dirty fields from the update
     * @return void
     */
    private static function handleTemporaryToPermanentConversion(Model $model, array $dirty): void
    {
        $wasTemporary = $model->getOriginal('is_temporary') === true;
        if (! isset($dirty['is_temporary']) || $dirty['is_temporary'] !== false || ! $wasTemporary) {
            return;
        }

        /**
         * Log file_uploaded event with permanent tags when file becomes permanent.
         * This ensures correct tags (permanent vs temporary) in audit logs.
         */
        $attributes = static::processSensitiveFields($model->getAttributes(), $model);
        $tagsString = static::getAuditTagsString($model);

        static::dispatchAuditLog(
            event: AuditLogEvent::FILE_UPLOADED,
            model: $model,
            oldValues: null,
            newValues: $attributes,
            tags: $tagsString
        );
    }

    /**
     * Get audit tags for this media file.
     *
     * Returns tags based on the file type and whether it's temporary.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        $tags = ['media'];

        if ($this->is_temporary) {
            $tags[] = 'temporary';
        } else {
            $tags[] = 'permanent';
        }

        if ($this->isImage()) {
            $tags[] = 'image';
        } else {
            $tags[] = 'file';
        }

        return $tags;
    }
}

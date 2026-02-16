<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserPreference Model
 *
 * Stores per-user preferences that override global settings.
 * Each user can have their own value for settings marked as user-overridable.
 */
class UserPreference extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_preferences';

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
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    /**
     * Get the user that owns this preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cast the value to the appropriate type based on the stored content.
     *
     * @return mixed
     */
    public function getCastedValueAttribute(): mixed
    {
        $value = $this->value;

        if ($value === null) {
            return null;
        }

        // Boolean handling
        if (in_array(strtolower($value), ['true', '1'], true)) {
            return true;
        }
        if (in_array(strtolower($value), ['false', '0'], true)) {
            return false;
        }

        // Numeric handling
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        return $value;
    }
}

<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\AuditLog\App\Traits\LogsActivity;
use Modules\Settings\Enums\SettingType;

class Setting extends Model
{
    use HasFactory, HasUuids, LogsActivity;

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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings_config';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'module',
        'group',
        'key',
        'value',
        'type',
        'label',
        'is_system',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'is_system' => 'boolean',
        ];
    }

    /**
     * Get the value attribute with automatic type casting.
     *
     * Casts the value based on the type column:
     * - boolean: returns bool
     * - integer: returns int
     * - text/textarea: returns string
     */
    public function getValueAttribute($value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            SettingType::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            SettingType::Integer => (int) $value,
            SettingType::Text, SettingType::Textarea => (string) $value,
        };
    }

    /**
     * Get audit tags for this setting.
     *
     * Returns tags based on the setting's module and group for better filtering.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        $tags = ['setting'];

        if ($this->module) {
            $tags[] = strtolower($this->module);
        }

        if ($this->group) {
            $tags[] = strtolower($this->group);
        }

        return $tags;
    }
}

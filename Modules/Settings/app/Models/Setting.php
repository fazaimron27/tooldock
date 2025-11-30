<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Settings\Enums\SettingType;

class Setting extends Model
{
    use HasFactory;

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
}

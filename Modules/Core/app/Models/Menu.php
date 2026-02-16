<?php

/**
 * Menu Model.
 *
 * Represents a navigation menu item with support for
 * hierarchical structures, ordering, and permission-based visibility.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AuditLog\Traits\LogsActivity;

class Menu extends Model
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
    protected $table = 'core_menus';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'group',
        'label',
        'route',
        'icon',
        'order',
        'permission',
        'module',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Get the parent menu.
     *
     * @return BelongsTo<Menu, Menu>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Get the child menus.
     *
     * @return HasMany<Menu>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    /**
     * Scope a query to filter by group.
     *
     * @param  Builder  $query  The query builder
     * @param  string  $group  The menu group to filter by
     * @return Builder
     */
    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Scope a query to only include active menus.
     *
     * @param  Builder  $query  The query builder
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include root menus (no parent).
     *
     * @param  Builder  $query  The query builder
     * @return Builder
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Ensure module is always lowercase.
     *
     * @param  string|null  $value  The module name to set
     * @return void
     */
    public function setModuleAttribute(?string $value): void
    {
        $this->attributes['module'] = $value ? strtolower($value) : null;
    }

    /**
     * Get audit tags for this menu.
     *
     * Returns tags based on the menu's module and group for better filtering.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        $tags = ['menu'];

        if ($this->module) {
            $tags[] = strtolower($this->module);
        }

        if ($this->group) {
            $tags[] = strtolower($this->group);
        }

        return $tags;
    }
}

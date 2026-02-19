<?php

/**
 * Category Model.
 *
 * Represents a hierarchical category with parent/child relationships,
 * type-scoped slugs, auto-slug generation, and audit logging.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Categories\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\AuditLog\Traits\LogsActivity;
use Modules\Categories\Database\Factories\CategoryFactory;

class Category extends Model
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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'type',
        'module',
        'color',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Get the parent category.
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Scope a query to filter by type.
     *
     * @param  Builder  $query  The query builder instance
     * @param  string  $type  The category type to filter by
     * @return Builder
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Ensure type is always lowercase.
     *
     * @param  string  $value  The type value to normalize
     * @return void
     */
    public function setTypeAttribute(string $value): void
    {
        $this->attributes['type'] = strtolower($value);
    }

    /**
     * Ensure module is always lowercase.
     *
     * @param  string|null  $value  The module value to normalize
     * @return void
     */
    public function setModuleAttribute(?string $value): void
    {
        $this->attributes['module'] = $value ? strtolower($value) : null;
    }

    /**
     * Generate a unique slug from the name.
     *
     * Slug must be unique per type (not globally).
     *
     * @param  string  $value  The name value to set and generate slug from
     * @return void
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;

        if (empty($this->attributes['slug'])) {
            $baseSlug = Str::slug($value);
            $slug = $baseSlug;
            $counter = 1;

            $type = $this->attributes['type'] ?? $this->type ?? null;

            $query = static::where('slug', $slug);

            if ($this->id) {
                $query->where('id', '!=', $this->id);
            }

            if ($type) {
                $query->where('type', $type);
            }

            while ($query->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;

                $query = static::where('slug', $slug);

                if ($this->id) {
                    $query->where('id', '!=', $this->id);
                }

                if ($type) {
                    $query->where('type', $type);
                }
            }

            $this->attributes['slug'] = $slug;
        }
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }

    /**
     * Get audit tags for this category.
     *
     * Returns tags based on the category's type and module for better filtering.
     *
     * @return array<string>
     */
    public function getAuditTags(): array
    {
        $tags = ['category'];

        if ($this->type) {
            $tags[] = strtolower($this->type);
        }

        if ($this->module) {
            $tags[] = strtolower($this->module);
        }

        return $tags;
    }
}

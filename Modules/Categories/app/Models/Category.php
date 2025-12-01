<?php

namespace Modules\Categories\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\AuditLog\App\Traits\LogsActivity;

class Category extends Model
{
    use HasFactory, LogsActivity;

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
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Ensure type is always lowercase.
     */
    public function setTypeAttribute(string $value): void
    {
        $this->attributes['type'] = strtolower($value);
    }

    /**
     * Ensure module is always lowercase.
     */
    public function setModuleAttribute(?string $value): void
    {
        $this->attributes['module'] = $value ? strtolower($value) : null;
    }

    /**
     * Generate a unique slug from the name.
     * Slug must be unique per type (not globally).
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;

        if (empty($this->attributes['slug'])) {
            $baseSlug = Str::slug($value);
            $slug = $baseSlug;
            $counter = 1;

            $type = $this->attributes['type'] ?? $this->type ?? null;

            $query = static::where('slug', $slug)
                ->where('id', '!=', $this->id ?? 0);

            if ($type) {
                $query->where('type', $type);
            }

            while ($query->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;

                $query = static::where('slug', $slug)
                    ->where('id', '!=', $this->id ?? 0);

                if ($type) {
                    $query->where('type', $type);
                }
            }

            $this->attributes['slug'] = $slug;
        }
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return \Modules\Categories\Database\Factories\CategoryFactory::new();
    }
}

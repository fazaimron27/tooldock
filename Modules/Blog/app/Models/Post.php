<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Blog\Database\Factories\PostFactory;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasUserOwnership;

class Post extends Model
{
    /** @use HasFactory<\Modules\Blog\Database\Factories\PostFactory> */
    use HasFactory, HasUserOwnership;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique slug from the title.
     */
    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = $value;

        if (empty($this->attributes['slug'])) {
            $baseSlug = Str::slug($value);
            $slug = $baseSlug;
            $counter = 1;

            while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $this->attributes['slug'] = $slug;
        }
    }

    /**
     * Check if the post is published.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at <= now();
    }

    /**
     * Check if the post is used in any campaigns.
     */
    public function isUsedInCampaigns(): bool
    {
        $campaignClass = 'Modules\\Newsletter\\Models\\Campaign';
        if (! class_exists($campaignClass)) {
            return false;
        }

        return $campaignClass::query()
            ->whereJsonContains('selected_posts', $this->id)
            ->exists();
    }

    /**
     * Check if the post is used in active campaigns (sending or sent).
     *
     * Prevents deletion of posts that are currently being sent or have been sent,
     * as these campaigns are considered immutable for data integrity.
     */
    public function isUsedInSentCampaigns(): bool
    {
        $campaignClass = 'Modules\\Newsletter\\Models\\Campaign';
        if (! class_exists($campaignClass)) {
            return false;
        }

        return $campaignClass::query()
            ->whereIn('status', ['sent', 'sending'])
            ->whereJsonContains('selected_posts', $this->id)
            ->exists();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return PostFactory
     */
    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}

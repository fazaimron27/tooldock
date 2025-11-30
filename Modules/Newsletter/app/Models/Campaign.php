<?php

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Models\User;
use Modules\Core\App\Traits\HasUserOwnership;
use Modules\Newsletter\Database\Factories\CampaignFactory;

/**
 * Campaign model representing an email newsletter campaign.
 *
 * Status values: 'draft', 'sending', 'sent'
 * selected_posts: JSON array of Blog Post IDs to include in the campaign
 */
class Campaign extends Model
{
    use HasFactory, HasUserOwnership;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'subject',
        'status',
        'content',
        'selected_posts',
        'scheduled_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selected_posts' => 'array',
            'scheduled_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the campaign.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

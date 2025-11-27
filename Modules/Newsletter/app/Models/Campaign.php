<?php

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Campaign model representing an email newsletter campaign.
 *
 * Status values: 'draft', 'sending', 'sent'
 * selected_posts: JSON array of Blog Post IDs to include in the campaign
 */
class Campaign extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
}

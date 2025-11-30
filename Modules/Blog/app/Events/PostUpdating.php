<?php

namespace Modules\Blog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Blog\Models\Post;

class PostUpdating
{
    use Dispatchable, SerializesModels;

    /**
     * The post being updated.
     */
    public Post $post;

    /**
     * Whether update should be prevented.
     */
    public bool $preventUpdate = false;

    /**
     * Optional reason for preventing update.
     */
    public ?string $preventionReason = null;

    /**
     * Create a new event instance.
     *
     * @param  Post  $post  The post being updated
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }
}

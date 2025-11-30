<?php

namespace Modules\Blog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Blog\Models\Post;

class PostDeleting
{
    use Dispatchable, SerializesModels;

    /**
     * The post being deleted.
     */
    public Post $post;

    /**
     * Whether deletion should be prevented.
     */
    public bool $preventDeletion = false;

    /**
     * Optional reason for preventing deletion.
     */
    public ?string $preventionReason = null;

    /**
     * Create a new event instance.
     *
     * @param  Post  $post  The post being deleted
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }
}

<?php

namespace Modules\Blog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * The ID of the deleted post.
     */
    public int $postId;

    /**
     * Snapshot of post data before deletion (for cleanup purposes).
     *
     * @var array{id: int, title: string, slug: string}
     */
    public array $postData;

    /**
     * Create a new event instance.
     *
     * @param  int  $postId  The ID of the deleted post
     * @param  array{id: int, title: string, slug: string}  $postData  Snapshot of post data before deletion
     */
    public function __construct(int $postId, array $postData)
    {
        $this->postId = $postId;
        $this->postData = $postData;
    }
}

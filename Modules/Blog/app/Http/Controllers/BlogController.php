<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Blog\Events\PostDeleted;
use Modules\Blog\Events\PostDeleting;
use Modules\Blog\Events\PostUpdating;
use Modules\Blog\Http\Requests\StorePostRequest;
use Modules\Blog\Http\Requests\UpdatePostRequest;
use Modules\Blog\Models\Post;

class BlogController extends Controller
{
    /**
     * Display a paginated listing of posts.
     *
     * Supports server-side search, sorting, and pagination.
     */
    public function index(DatatableQueryService $datatableService): Response
    {
        $this->authorize('viewAny', Post::class);

        $query = Post::with('user')->forUser();

        $defaultPerPage = (int) settings('posts_per_page', 10);

        $posts = $datatableService->build($query, [
            'searchFields' => ['title', 'excerpt', 'content'],
            'allowedSorts' => ['title', 'created_at', 'published_at', 'updated_at'],
            'defaultSort' => 'created_at',
            'defaultDirection' => 'desc',
            'allowedPerPage' => [10, 20, 30, 50],
            'defaultPerPage' => $defaultPerPage,
        ]);

        return Inertia::render('Modules::Blog/Index', [
            'posts' => $posts,
            'defaultPerPage' => $defaultPerPage,
        ]);
    }

    /**
     * Display the form for creating a new post.
     */
    public function create(): Response
    {
        $this->authorize('create', Post::class);

        return Inertia::render('Modules::Blog/Create');
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $post = Post::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('blog.index')
            ->with('success', 'Post created successfully.');
    }

    /**
     * Display the specified post.
     */
    public function show(Post $blog): Response|RedirectResponse
    {
        $this->authorize('view', $blog);

        $blog->load('user');

        return Inertia::render('Modules::Blog/Show', [
            'post' => $blog,
        ]);
    }

    /**
     * Display the form for editing the specified post.
     */
    public function edit(Post $blog): Response|RedirectResponse
    {
        $this->authorize('update', $blog);

        return Inertia::render('Modules::Blog/Edit', [
            'post' => $blog,
        ]);
    }

    /**
     * Update the specified post in storage.
     *
     * Fires PostUpdating event to allow listeners to prevent updates (e.g., if used in sending campaigns).
     */
    public function update(UpdatePostRequest $request, Post $blog): RedirectResponse
    {
        $this->authorize('update', $blog);

        $event = new PostUpdating($blog);
        Event::dispatch($event);

        if ($event->preventUpdate) {
            return redirect()->route('blog.index')
                ->with('error', $event->preventionReason ?? 'Cannot update post. It is currently being used in one or more campaigns that are being sent.');
        }

        $blog->update($request->validated());

        return redirect()->route('blog.index')
            ->with('success', 'Post updated successfully.');
    }

    /**
     * Delete the specified post.
     *
     * Fires PostDeleting event to allow listeners to prevent deletion (e.g., if used in campaigns).
     * If deletion is allowed, fires PostDeleted event for cleanup operations.
     */
    public function destroy(Post $blog): RedirectResponse
    {
        $this->authorize('delete', $blog);

        $event = new PostDeleting($blog);
        Event::dispatch($event);

        if ($event->preventDeletion) {
            return redirect()->route('blog.index')
                ->with('error', $event->preventionReason ?? 'Cannot delete post. It is used in one or more active campaigns (sending or sent).');
        }

        DB::transaction(function () use ($blog): void {
            $postData = $blog->only(['id', 'title', 'slug']);
            $postId = $blog->id;

            $blog->delete();

            Event::dispatch(new PostDeleted($postId, $postData));
        });

        return redirect()->route('blog.index')
            ->with('success', 'Post deleted successfully.');
    }
}

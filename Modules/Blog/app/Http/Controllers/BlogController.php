<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DatatableQueryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Blog\Http\Requests\StorePostRequest;
use Modules\Blog\Http\Requests\UpdatePostRequest;
use Modules\Blog\Models\Post;

class BlogController extends Controller
{
    /**
     * Display a paginated listing of posts for the authenticated user.
     *
     * Supports server-side search, sorting, and pagination. Only displays
     * posts belonging to the logged-in user.
     */
    public function index(DatatableQueryService $datatableService): Response
    {
        $query = Post::with('user')
            ->where('user_id', request()->user()->id);

        $defaultPerPage = 10;

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
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Modules::Blog/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request): RedirectResponse
    {
        $post = Post::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('blog.index')
            ->with('success', 'Post created successfully.');
    }

    /**
     * Show the specified resource.
     */
    public function show(Post $blog): Response
    {
        $blog->load('user');

        return Inertia::render('Modules::Blog/Show', [
            'post' => $blog,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $blog): Response
    {
        return Inertia::render('Modules::Blog/Edit', [
            'post' => $blog,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $blog): RedirectResponse
    {
        $blog->update($request->validated());

        return redirect()->route('blog.index')
            ->with('success', 'Post updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $blog): RedirectResponse
    {
        $blog->delete();

        return redirect()->route('blog.index')
            ->with('success', 'Post deleted successfully.');
    }
}

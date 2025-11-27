<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Blog\Http\Requests\StorePostRequest;
use Modules\Blog\Http\Requests\UpdatePostRequest;
use Modules\Blog\Models\Post;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $query = Post::with('user');

        // Search
        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = request()->get('sort', 'created_at');
        $direction = request()->get('direction', 'desc');

        // Validate sort column
        $allowedSorts = ['title', 'created_at', 'published_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        // Validate direction
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);

        // Pagination
        $perPage = request()->get('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 30, 50]) ? $perPage : 10;

        $posts = $query->paginate($perPage);

        return Inertia::render('Modules::Blog/Index', [
            'posts' => $posts,
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

<?php

/**
 * Nucleus Controller
 *
 * Handles the core JSON editor interactions: loading the UI,
 * saving snippets, retrieving snippet history, and deleting snippets.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Nucleus\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Nucleus\Http\Requests\StoreSnippetRequest;
use Modules\Nucleus\Models\NucleusSnippet;

/**
 * Class NucleusController
 *
 * Provides endpoints for the Nucleus JSON editor: the main editor view,
 * snippet persistence, history retrieval, and snippet deletion.
 */
class NucleusController extends Controller
{
    /**
     * Load the main React editor UI with recent snippets.
     *
     * @return Response Inertia response rendering the Nucleus editor page
     */
    public function index(): Response
    {
        $this->authorize('viewAny', NucleusSnippet::class);

        $snippets = NucleusSnippet::forUser()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['id', 'title', 'created_at']);

        return Inertia::render('Modules::Nucleus/Index', [
            'snippets' => $snippets,
            'editorSettings' => [
                'theme' => config('nucleus.editor_theme'),
                'wordWrap' => config('nucleus.word_wrap'),
                'fontSize' => config('nucleus.font_size'),
            ],
        ]);
    }

    /**
     * Save a JSON snippet to the database.
     *
     * @param  StoreSnippetRequest  $request  Validated snippet creation request
     * @return JsonResponse JSON response with the created snippet
     */
    public function store(StoreSnippetRequest $request): JsonResponse
    {
        $snippet = NucleusSnippet::create([
            'user_id' => $request->user()->getKey(),
            'title' => $request->validated('title'),
            'raw_json' => $request->validated('raw_json'),
        ]);

        return response()->json([
            'snippet' => $snippet->only(['id', 'title', 'created_at']),
            'message' => 'Snippet saved successfully.',
        ], 201);
    }

    /**
     * Retrieve paginated saved snippets for the history panel.
     *
     * @return JsonResponse JSON response with paginated snippets list
     */
    public function history(): JsonResponse
    {
        $this->authorize('viewAny', NucleusSnippet::class);

        $snippets = NucleusSnippet::forUser()
            ->orderBy('created_at', 'desc')
            ->paginate(20, ['id', 'title', 'created_at']);

        return response()->json($snippets);
    }

    /**
     * Load a snippet's raw JSON for editing in the editor.
     *
     * @param  NucleusSnippet  $snippet  The snippet to load
     * @return JsonResponse JSON response with the snippet data
     */
    public function show(NucleusSnippet $snippet): JsonResponse
    {
        $this->authorize('view', $snippet);

        return response()->json([
            'snippet' => $snippet->only(['id', 'title', 'raw_json']),
        ]);
    }

    /**
     * Delete a saved snippet.
     *
     * @param  NucleusSnippet  $snippet  The snippet to delete
     * @return JsonResponse JSON response confirming deletion
     */
    public function destroy(NucleusSnippet $snippet): JsonResponse
    {
        $this->authorize('delete', $snippet);

        $snippet->delete();

        return response()->json(['message' => 'Snippet deleted.']);
    }
}

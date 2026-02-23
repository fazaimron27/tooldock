<?php

/**
 * QuickDraw Controller
 *
 * Handles CRUD operations and tldraw state synchronization for whiteboard canvases.
 * Integrates with CacheService for document state caching.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\QuickDraw\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Cache\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\QuickDraw\Models\QuickDraw;

/**
 * Class QuickDrawController
 *
 * Provides endpoints for listing canvases, viewing the tldraw editor,
 * creating/deleting canvases, and syncing tldraw document state.
 * Uses CacheService for caching canvas listings and document state.
 *
 * @see \Modules\QuickDraw\Models\QuickDraw
 * @see \Modules\QuickDraw\Policies\QuickDrawPolicy
 */
class QuickDrawController extends Controller
{
    private const CACHE_TAG = 'quickdraw';

    private const CACHE_TTL_HOURS = 24;

    /**
     * Create a new controller instance.
     *
     * @param  CacheService  $cacheService  Injected cache service for state caching
     * @return void
     */
    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Display a listing of all canvases.
     *
     * @return Response Inertia response rendering the canvas index page
     */
    public function index(): Response
    {
        $this->authorize('viewAny', QuickDraw::class);

        $userId = Auth::id();

        $quickdraws = $this->cacheService->remember(
            "quickdraw:list:{$userId}",
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => QuickDraw::forUser()
                ->latest()
                ->get(['id', 'name', 'description', 'created_at', 'updated_at']),
            self::CACHE_TAG,
            'QuickDrawController'
        );

        return Inertia::render('Modules::QuickDraw/Index', [
            'quickdraws' => $quickdraws,
        ]);
    }

    /**
     * Show the tldraw editor for a specific canvas.
     *
     * Document state is cached to avoid querying the potentially large
     * JSON blob from the database on every page load.
     *
     * @param  QuickDraw  $quickdraw  The canvas to display
     * @return Response Inertia response rendering the tldraw editor
     */
    public function show(QuickDraw $quickdraw): Response
    {
        $this->authorize('view', $quickdraw);

        $documentState = $this->cacheService->remember(
            "quickdraw:state:{$quickdraw->id}",
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => $quickdraw->state?->document_state,
            self::CACHE_TAG,
            'QuickDrawController'
        );

        return Inertia::render('Modules::QuickDraw/Show', [
            'quickdraw' => $quickdraw->only('id', 'name', 'description'),
            'documentState' => $documentState,
        ]);
    }

    /**
     * Store a newly created canvas.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return RedirectResponse Redirect to the new canvas editor
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', QuickDraw::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $quickdraw = QuickDraw::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        $this->cacheService->forget(
            "quickdraw:list:{$request->user()->id}",
            self::CACHE_TAG,
            'QuickDrawController'
        );

        return redirect()->route('quickdraw.show', $quickdraw)
            ->with('success', 'Canvas created successfully.');
    }

    /**
     * Sync the tldraw document state for a canvas.
     *
     * This endpoint is called by the frontend auto-save hook to persist
     * the tldraw editor state as a JSON snapshot.
     *
     * @param  Request  $request  The incoming HTTP request with document_state
     * @param  QuickDraw  $quickdraw  The canvas to sync
     * @return JsonResponse JSON response confirming the sync
     */
    public function sync(Request $request, QuickDraw $quickdraw): JsonResponse
    {
        $this->authorize('update', $quickdraw);

        $request->validate([
            'document_state' => 'required|array',
        ]);

        $body = json_decode($request->getContent());
        $encoded = json_encode($body->document_state);

        $quickdraw->state()->updateOrCreate(
            ['quickdraw_id' => $quickdraw->id],
            ['document_state' => $encoded]
        );

        $this->cacheService->put(
            "quickdraw:state:{$quickdraw->id}",
            $encoded,
            now()->addHours(self::CACHE_TTL_HOURS),
            self::CACHE_TAG,
            'QuickDrawController'
        );

        return response()->json(['status' => 'synced']);
    }

    /**
     * Remove the specified canvas.
     *
     * @param  QuickDraw  $quickdraw  The canvas to delete
     * @return RedirectResponse Redirect to canvas index with success message
     */
    public function destroy(QuickDraw $quickdraw): RedirectResponse
    {
        $this->authorize('delete', $quickdraw);

        $quickdraw->delete();

        $this->cacheService->forget(
            "quickdraw:state:{$quickdraw->id}",
            self::CACHE_TAG,
            'QuickDrawController'
        );
        $this->cacheService->forget(
            'quickdraw:list:'.Auth::id(),
            self::CACHE_TAG,
            'QuickDrawController'
        );

        return redirect()->route('quickdraw.index')
            ->with('success', 'Canvas deleted successfully.');
    }
}

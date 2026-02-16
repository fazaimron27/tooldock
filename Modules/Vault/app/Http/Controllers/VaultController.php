<?php

/**
 * Vault Controller
 *
 * Handles CRUD operations for vault items including listing, creating,
 * editing, deleting, password generation, favorites, and TOTP code generation.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Cache\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Categories\Models\Category;
use Modules\Vault\Http\Requests\StoreVaultRequest;
use Modules\Vault\Http\Requests\UpdateVaultRequest;
use Modules\Vault\Models\Vault;

/**
 * Class VaultController
 *
 * Provides endpoints for managing vault items (passwords, cards, notes, servers).
 * Integrates with CacheService for category caching and supports search/filter
 * functionality, favorite toggling, and server-side TOTP code generation.
 *
 * @see \Modules\Vault\Models\Vault
 * @see \Modules\Vault\Policies\VaultPolicy
 */
class VaultController extends Controller
{
    private const CACHE_TAG = 'categories';

    private const CACHE_TTL_HOURS = 24;

    /**
     * Create a new controller instance.
     *
     * @param  CacheService  $cacheService  Injected cache service for category caching
     * @return void
     */
    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Get categories for dropdowns with caching.
     *
     * Categories are cached for 24 hours to reduce database queries.
     * Cache is automatically invalidated when categories are modified.
     * Only returns categories with type 'vault'.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getCategories()
    {
        return $this->cacheService->remember(
            'vault:categories:dropdown',
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => Category::byType('vault')->orderBy('name')->get(['id', 'name', 'color']),
            self::CACHE_TAG,
            'VaultController'
        );
    }

    /**
     * Display a paginated listing of vaults.
     *
     * Supports search, filtering by type, category, and favorite status.
     *
     * @param  Request  $request  The incoming HTTP request with optional filters
     * @return Response Inertia response rendering the vault index page
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vault::class);

        $query = Vault::forUser()->with('category');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('username', 'ilike', "%{$search}%")
                    ->orWhere('url', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('favorite') && $request->favorite) {
            $query->where('is_favorite', true);
        }

        $defaultPerPage = (int) settings('vault_per_page', 20);
        $perPage = (int) ($request->get('per_page', $defaultPerPage));

        $vaults = $query->orderBy('is_favorite', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $categories = $this->getCategories();
        $types = Vault::TYPES;

        return Inertia::render('Modules::Vault/Index', [
            'vaults' => $vaults,
            'categories' => $categories,
            'types' => $types,
        ]);
    }

    /**
     * Show the form for creating a new vault.
     *
     * @return Response Inertia response rendering the vault creation form
     */
    public function create(): Response
    {
        $this->authorize('create', Vault::class);

        $categories = $this->getCategories();
        $types = Vault::TYPES;

        return Inertia::render('Modules::Vault/Create', [
            'categories' => $categories,
            'types' => $types,
        ]);
    }

    /**
     * Store a newly created vault in storage.
     *
     * @param  StoreVaultRequest  $request  Validated vault creation request
     * @return RedirectResponse Redirect to vault index with success message
     */
    public function store(StoreVaultRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        Vault::create($data);

        return redirect()->route('vault.index')
            ->with('success', 'Vault item created successfully.');
    }

    /**
     * Show the form for editing the specified vault.
     *
     * @param  Vault  $vault  The vault model to edit
     * @return Response Inertia response rendering the vault edit form
     */
    public function edit(Vault $vault): Response
    {
        $this->authorize('update', $vault);

        $categories = $this->getCategories();
        $types = Vault::TYPES;

        return Inertia::render('Modules::Vault/Edit', [
            'vault' => $vault,
            'categories' => $categories,
            'types' => $types,
        ]);
    }

    /**
     * Update the specified vault in storage.
     *
     * @param  UpdateVaultRequest  $request  Validated vault update request
     * @param  Vault  $vault  The vault model to update
     * @return RedirectResponse Redirect to vault index with success message
     */
    public function update(UpdateVaultRequest $request, Vault $vault): RedirectResponse
    {
        $vault->update($request->validated());

        return redirect()->route('vault.index')
            ->with('success', 'Vault item updated successfully.');
    }

    /**
     * Remove the specified vault from storage.
     *
     * @param  Vault  $vault  The vault model to delete
     * @return RedirectResponse Redirect to vault index with success message
     */
    public function destroy(Vault $vault): RedirectResponse
    {
        $this->authorize('delete', $vault);

        $vault->delete();

        return redirect()->route('vault.index')
            ->with('success', 'Vault item deleted successfully.');
    }

    /**
     * Generate a cryptographically secure random password.
     *
     * @return JsonResponse JSON response containing the generated password
     */
    public function generatePassword(): JsonResponse
    {
        $password = bin2hex(random_bytes(16));

        return response()->json(['password' => $password]);
    }

    /**
     * Toggle favorite status for a vault.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Vault  $vault  The vault model to toggle
     * @return JsonResponse|RedirectResponse JSON or redirect depending on request type
     */
    public function toggleFavorite(Request $request, Vault $vault): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $vault);

        $vault->is_favorite = ! $vault->is_favorite;
        $vault->save();

        if ($request->header('X-Inertia')) {
            return back();
        }

        return response()->json([
            'is_favorite' => $vault->is_favorite,
        ]);
    }

    /**
     * Generate TOTP code for a vault (server-side).
     *
     * This endpoint generates TOTP codes server-side to avoid exposing
     * the decrypted secret to the frontend. The secret is decrypted
     * only on the server and never sent to the client.
     *
     * @param  Vault  $vault  The vault model containing the TOTP secret
     * @return JsonResponse JSON response with the generated code or error
     */
    public function generateTotp(Vault $vault): JsonResponse
    {
        $this->authorize('view', $vault);

        $code = $vault->generateCurrentTotpCode();

        if ($code === null) {
            return response()->json([
                'error' => 'No TOTP secret configured for this vault',
            ], 400);
        }

        return response()->json([
            'code' => $code,
        ]);
    }
}

<?php

namespace Modules\Core\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Core\App\Models\User;

class UserController extends Controller
{
    /**
     * Search users by name or email for autocomplete/combobox.
     *
     * Supports searching by ID for direct lookups.
     * Returns optimized JSON response for combobox usage.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $search = trim($request->input('search', ''));
        $id = $request->input('id');
        $limit = min((int) $request->input('limit', 20), 50);

        /**
         * Handle direct user lookup by ID for pre-selected users in combobox.
         * Returns single user in expected format for frontend consumption.
         */
        if ($id) {
            $user = User::select('id', 'name', 'email')
                ->find($id);

            if (! $user) {
                return response()->json(['data' => []]);
            }

            return response()->json([
                'data' => [
                    [
                        'value' => (string) $user->id,
                        'label' => "{$user->name} ({$user->email})",
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
            ]);
        }

        /**
         * Cache user search results using tags for efficient bulk invalidation.
         * Tags allow clearing all user search caches when users are created/updated/deleted.
         */
        $cacheKey = "users.search.{$search}.{$limit}";
        $cacheTTL = config('core.user_search_cache_ttl', 300);

        try {
            $users = Cache::tags(['users', 'user_search'])->remember($cacheKey, $cacheTTL, function () use ($search, $limit) {
                return $this->buildUserSearchQuery($search, $limit)->get();
            });
        } catch (\Exception $e) {
            /**
             * Fallback for cache drivers that don't support tags (file, database, dynamodb).
             * Uses standard cache without tags, relying on TTL for expiration.
             */
            $users = Cache::remember($cacheKey, $cacheTTL, function () use ($search, $limit) {
                return $this->buildUserSearchQuery($search, $limit)->get();
            });
        }

        return response()->json([
            'data' => $users->map(fn ($user) => [
                'value' => (string) $user->id,
                'label' => "{$user->name} ({$user->email})",
                'name' => $user->name,
                'email' => $user->email,
            ])->values()->all(),
        ]);
    }

    /**
     * Build a query for searching users by name or email.
     *
     * @param  string  $search  Search term to filter by name or email
     * @param  int  $limit  Maximum number of results to return
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildUserSearchQuery(string $search, int $limit): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->limit($limit);

        if ($search) {
            $searchTerm = "%{$search}%";
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'ilike', $searchTerm)
                    ->orWhere('email', 'ilike', $searchTerm);
            });
        }

        return $query;
    }
}

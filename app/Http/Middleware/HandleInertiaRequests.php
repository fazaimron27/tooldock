<?php

/**
 * Handle Inertia Requests Middleware.
 *
 * Extends the Inertia middleware to inject shared props into every Inertia
 * response. Delegates shared data assembly to InertiaSharedDataService for
 * clean separation of concerns.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Http\Middleware;

use App\Services\Core\InertiaSharedDataService;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Inertia middleware for sharing global props with the frontend.
 *
 * Merges parent shared data with application-specific shared data
 * assembled by InertiaSharedDataService, including flash messages,
 * CSRF tokens, module pages, and module-registered data providers.
 *
 * @see InertiaSharedDataService Assembles the shared data payload
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * Create a new middleware instance.
     *
     * @param  InertiaSharedDataService  $sharedDataService  Service for assembling shared Inertia data
     */
    public function __construct(
        private InertiaSharedDataService $sharedDataService
    ) {}

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(
            parent::share($request),
            $this->sharedDataService->getSharedData($request)
        );
    }
}

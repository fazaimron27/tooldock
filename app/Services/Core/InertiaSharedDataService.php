<?php

/**
 * Inertia Shared Data Service.
 *
 * Aggregates and prepares shared props for all Inertia responses, including
 * flash messages, CSRF tokens, module page manifests, and module-specific
 * shared data registered through the InertiaSharedDataRegistry.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Services\Core;

use App\Services\Modules\ModulePageManifestService;
use App\Services\Registry\InertiaSharedDataRegistry;
use Illuminate\Http\Request;

/**
 * Prepares shared props for all Inertia responses.
 *
 * Merges core application data (flash messages, CSRF tokens, module pages)
 * with module-registered shared data to provide a unified data context
 * for the frontend SPA.
 *
 * @see InertiaSharedDataRegistry Registry for module-specific shared data callbacks
 * @see ModulePageManifestService Service for discovering available module pages
 */
class InertiaSharedDataService
{
    /**
     * Create a new Inertia shared data service instance.
     *
     * @param  InertiaSharedDataRegistry  $sharedDataRegistry  Registry containing module-specific shared data callbacks
     * @param  ModulePageManifestService  $modulePageManifest  Service for discovering available module pages
     */
    public function __construct(
        private InertiaSharedDataRegistry $sharedDataRegistry,
        private ModulePageManifestService $modulePageManifest
    ) {}

    /**
     * Build shared props array including flash messages, CSRF, and module data.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming request
     * @return array<string, mixed> Shared props for Inertia responses
     */
    public function getSharedData(Request $request): array
    {
        return array_merge(
            [
                'flash' => [
                    'success' => $request->session()->pull('success'),
                    'error' => $request->session()->pull('error'),
                    'warning' => $request->session()->pull('warning'),
                ],
                'csrf' => csrf_token(),
                'modulePages' => $this->modulePageManifest->getAvailablePages(),
            ],
            $this->sharedDataRegistry->getSharedData($request)
        );
    }
}

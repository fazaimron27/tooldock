<?php

namespace App\Services\Core;

use App\Services\Modules\ModulePageManifestService;
use App\Services\Registry\InertiaSharedDataRegistry;
use Illuminate\Http\Request;

/**
 * Prepares shared props for all Inertia responses.
 */
class InertiaSharedDataService
{
    public function __construct(
        private InertiaSharedDataRegistry $sharedDataRegistry,
        private ModulePageManifestService $modulePageManifest
    ) {}

    /**
     * Build shared props array including flash messages, CSRF, and module data.
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

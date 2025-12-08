<?php

namespace App\Services\Core;

use App\Services\Registry\InertiaSharedDataRegistry;
use Illuminate\Http\Request;

/**
 * Service for preparing shared data for Inertia responses.
 *
 * Centralizes all logic for preparing data that should be shared
 * with every Inertia response, including flash messages and CSRF token.
 * Module-specific data (auth, menus, etc.) is registered via InertiaSharedDataRegistry.
 */
class InertiaSharedDataService
{
    public function __construct(
        private InertiaSharedDataRegistry $sharedDataRegistry
    ) {}

    /**
     * Get all shared data for Inertia responses.
     *
     * Prepares and returns the complete array of shared props that
     * will be available to all Inertia components.
     *
     * @param  \Illuminate\Http\Request  $request  The current request
     * @return array<string, mixed>
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
            ],
            $this->sharedDataRegistry->getSharedData($request)
        );
    }
}

<?php

namespace App\Services\Core;

use App\Services\Media\MediaConfigService;
use App\Services\Registry\MenuRegistry;
use Illuminate\Http\Request;

/**
 * Service for preparing shared data for Inertia responses.
 *
 * Centralizes all logic for preparing data that should be shared
 * with every Inertia response, including auth, menus, flash messages,
 * app configuration, and media settings.
 */
class InertiaSharedDataService
{
    public function __construct(
        private MenuRegistry $menuRegistry,
        private MediaConfigService $mediaConfigService
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
        $user = $request->user();

        if ($user) {
            $user->load(['avatar', 'roles']);
        }

        $fileSizeInfo = $this->mediaConfigService->getFileSizeInfo();

        return [
            'auth' => [
                'user' => $user ? [
                    ...$user->toArray(),
                    'avatar_url' => $user->avatar?->url,
                ] : null,
            ],
            'menus' => $this->menuRegistry->getMenus($user),
            'flash' => [
                'success' => $request->session()->pull('success'),
                'error' => $request->session()->pull('error'),
                'warning' => $request->session()->pull('warning'),
            ],
            'app_name' => settings('app_name', config('app.name')),
            'app_logo' => settings('app_logo', 'Cog'),
            'csrf' => csrf_token(),
            'media' => [
                'max_file_size_kb' => $fileSizeInfo['effective_kb'],
                'max_file_size_mb' => $fileSizeInfo['effective_mb'],
                'php_upload_max_filesize_kb' => $fileSizeInfo['php_upload_max_kb'],
                'php_post_max_size_kb' => $fileSizeInfo['php_post_max_kb'],
                'is_php_limit' => $fileSizeInfo['is_php_limited'],
            ],
        ];
    }
}

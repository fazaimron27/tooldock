<?php

namespace App\Http\Middleware;

use App\Services\InertiaSharedDataService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
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

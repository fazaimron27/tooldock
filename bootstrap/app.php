<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreloadUserRelations;
use App\Services\Core\ExceptionResponseService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->web(append: [
            PreloadUserRelations::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $exceptionService = app(ExceptionResponseService::class);

            // Handle Inertia 403 responses
            if ($inertiaResponse = $exceptionService->handleInertiaForbidden($response, $request)) {
                return $inertiaResponse;
            }

            // Handle API responses
            if ($apiResponse = $exceptionService->handleApiResponse($response, $request)) {
                return $apiResponse;
            }

            return $response;
        });
    })->create();

<?php

use App\Services\ExceptionResponseService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
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

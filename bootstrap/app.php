<?php

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
            $statusCode = $response->getStatusCode();

            if ($statusCode === 403 && $request->header('X-Inertia')) {
                return back()->with('error', 'This action is unauthorized.');
            }

            if ($request->expectsJson() || $request->is('api/*') || $request->is('*/api/*')) {
                $message = 'An error occurred.';
                $errorTitle = 'Error';

                switch ($statusCode) {
                    case 400:
                        $errorTitle = 'Bad Request';
                        $message = 'Invalid request. Please check your input and try again.';
                        break;
                    case 401:
                        $errorTitle = 'Unauthorized';
                        $message = 'You are not authorized. Please log in and try again.';
                        break;
                    case 403:
                        $errorTitle = 'Forbidden';
                        $message = 'You do not have permission to perform this action.';
                        break;
                    case 404:
                        $errorTitle = 'Not Found';
                        $message = 'The requested resource was not found.';
                        break;
                    case 413:
                        $errorTitle = 'File too large';
                        $message = app(\App\Services\MediaConfigService::class)->getFileTooLargeErrorMessage();
                        break;
                    case 419:
                        $errorTitle = 'Page Expired';
                        $message = 'The page has expired. Please refresh and try again.';
                        break;
                    case 422:
                        $errorTitle = 'Validation Failed';
                        $message = 'Validation failed. Please check your input.';
                        break;
                    case 429:
                        $errorTitle = 'Too Many Requests';
                        $message = 'Too many requests. Please wait a moment and try again.';
                        break;
                    case 500:
                        $errorTitle = 'Server Error';
                        $message = app()->environment('production')
                            ? 'A server error occurred. Please try again later.'
                            : 'Internal server error occurred.';
                        break;
                    case 502:
                        $errorTitle = 'Bad Gateway';
                        $message = 'Server is temporarily unavailable. Please try again later.';
                        break;
                    case 503:
                        $errorTitle = 'Service Unavailable';
                        $message = 'Service is temporarily unavailable. Please try again later.';
                        break;
                    default:
                        if ($statusCode >= 400 && $statusCode < 500) {
                            $errorTitle = 'Client Error';
                            $message = 'A client error occurred. Please check your request.';
                        } elseif ($statusCode >= 500) {
                            $errorTitle = 'Server Error';
                            $message = app()->environment('production')
                                ? 'A server error occurred. Please try again later.'
                                : 'A server error occurred.';
                        }
                }

                return response()->json([
                    'message' => $message,
                    'error' => $errorTitle,
                ], $statusCode);
            }

            return $response;
        });
    })->create();

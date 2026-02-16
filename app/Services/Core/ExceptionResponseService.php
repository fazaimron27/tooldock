<?php

/**
 * Exception Response Service.
 *
 * Provides centralized handling of HTTP error responses for both API and
 * Inertia requests. Maps HTTP status codes to user-friendly error messages
 * and titles, ensuring consistent error presentation across the application.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace App\Services\Core;

use App\Services\Media\MediaConfigService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Centralized exception response handler.
 *
 * Handles error response formatting for API (JSON) and Inertia (redirect)
 * requests, providing human-readable error messages for all common HTTP
 * status codes including 4xx client errors and 5xx server errors.
 *
 * @see MediaConfigService Used for dynamic file size error messages
 */
class ExceptionResponseService
{
    /**
     * Create a new exception response service instance.
     *
     * @param  MediaConfigService  $mediaConfigService  Service for retrieving media configuration (e.g., max file size)
     */
    public function __construct(
        private MediaConfigService $mediaConfigService
    ) {}

    /**
     * Handle exception response for API requests.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response  The original response
     * @param  \Illuminate\Http\Request  $request  The incoming request
     * @return \Symfony\Component\HttpFoundation\Response|null JSON error response, or null if not an API request
     */
    public function handleApiResponse(Response $response, Request $request): ?Response
    {
        if (! $request->expectsJson() && ! $request->is('api/*') && ! $request->is('*/api/*')) {
            return null;
        }

        $statusCode = $response->getStatusCode();
        $errorData = $this->getErrorData($statusCode);

        return response()->json([
            'message' => $errorData['message'],
            'error' => $errorData['title'],
        ], $statusCode);
    }

    /**
     * Handle Inertia 403 responses.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response  The original response
     * @param  \Illuminate\Http\Request  $request  The incoming request
     * @return \Symfony\Component\HttpFoundation\Response|null Redirect response, or null if not an Inertia 403
     */
    public function handleInertiaForbidden(Response $response, Request $request): ?Response
    {
        if ($response->getStatusCode() === 403 && $request->header('X-Inertia')) {
            return back()->with('error', 'This action is unauthorized.');
        }

        return null;
    }

    /**
     * Get error data for a given status code.
     *
     * @param  int  $statusCode  The HTTP status code
     * @return array{title: string, message: string}
     */
    private function getErrorData(int $statusCode): array
    {
        return match ($statusCode) {
            400 => [
                'title' => 'Bad Request',
                'message' => 'Invalid request. Please check your input and try again.',
            ],
            401 => [
                'title' => 'Unauthorized',
                'message' => 'You are not authorized. Please log in and try again.',
            ],
            403 => [
                'title' => 'Forbidden',
                'message' => 'You do not have permission to perform this action.',
            ],
            404 => [
                'title' => 'Not Found',
                'message' => 'The requested resource was not found.',
            ],
            413 => [
                'title' => 'File too large',
                'message' => $this->mediaConfigService->getFileTooLargeErrorMessage(),
            ],
            419 => [
                'title' => 'Page Expired',
                'message' => 'The page has expired. Please refresh and try again.',
            ],
            422 => [
                'title' => 'Validation Failed',
                'message' => 'Validation failed. Please check your input.',
            ],
            429 => [
                'title' => 'Too Many Requests',
                'message' => 'Too many requests. Please wait a moment and try again.',
            ],
            500 => [
                'title' => 'Server Error',
                'message' => app()->environment('production')
                    ? 'A server error occurred. Please try again later.'
                    : 'Internal server error occurred.',
            ],
            502 => [
                'title' => 'Bad Gateway',
                'message' => 'Server is temporarily unavailable. Please try again later.',
            ],
            503 => [
                'title' => 'Service Unavailable',
                'message' => 'Service is temporarily unavailable. Please try again later.',
            ],
            default => $this->getDefaultErrorData($statusCode),
        };
    }

    /**
     * Get default error data for status codes not explicitly handled.
     *
     * @param  int  $statusCode  The HTTP status code
     * @return array{title: string, message: string}
     */
    private function getDefaultErrorData(int $statusCode): array
    {
        if ($statusCode >= 400 && $statusCode < 500) {
            return [
                'title' => 'Client Error',
                'message' => 'A client error occurred. Please check your request.',
            ];
        }

        if ($statusCode >= 500) {
            return [
                'title' => 'Server Error',
                'message' => app()->environment('production')
                    ? 'A server error occurred. Please try again later.'
                    : 'A server error occurred.',
            ];
        }

        return [
            'title' => 'Error',
            'message' => 'An error occurred.',
        ];
    }
}

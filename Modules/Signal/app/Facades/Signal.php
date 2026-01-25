<?php

/**
 * Signal Facade
 *
 * Laravel Facade providing static access to the SignalService.
 * Allows other modules to send notifications without directly
 * instantiating the service class.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Signal\Services\SignalService;

/**
 * Class Signal
 *
 * Static proxy for SendingService, providing a clean API for
 * dispatching notifications from anywhere in the application.
 *
 * @method static void info(\Illuminate\Contracts\Auth\Authenticatable $user, string $title, string $message, ?string $url = null, ?string $moduleSource = null, ?string $category = null) Send an informational notification
 * @method static void success(\Illuminate\Contracts\Auth\Authenticatable $user, string $title, string $message, ?string $url = null, ?string $moduleSource = null, ?string $category = null) Send a success notification
 * @method static void warning(\Illuminate\Contracts\Auth\Authenticatable $user, string $title, string $message, ?string $url = null, ?string $moduleSource = null, ?string $category = null) Send a warning notification
 * @method static void alert(\Illuminate\Contracts\Auth\Authenticatable $user, string $title, string $message, ?string $url = null, ?string $moduleSource = null, ?string $category = null) Send an alert/error notification
 * @method static void send(\Illuminate\Contracts\Auth\Authenticatable $user, string $title, string $message, string $type = 'info', ?string $url = null, ?string $moduleSource = null, ?string $category = null) Send a notification with custom type
 *
 * @see \Modules\Signal\Services\SignalService
 */
class Signal extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * Returns the service class that this facade proxies to.
     * The service is resolved from the container as a singleton.
     *
     * @return string The service class name
     */
    protected static function getFacadeAccessor(): string
    {
        return SignalService::class;
    }
}

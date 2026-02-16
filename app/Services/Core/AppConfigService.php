<?php

/**
 * Application Config Service
 *
 * Synchronizes application configuration from the settings
 * table, mapping setting keys to Laravel config paths with
 * optional value transformers.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace App\Services\Core;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for synchronizing application configuration from settings.
 *
 * Centralizes the logic for updating Laravel config values from the
 * settings system, ensuring proper handling during migrations.
 */
class AppConfigService
{
    /**
     * Mapping of setting keys to their config paths and value transformers.
     *
     * @var array<string, array{path: string|array<string>, transform?: callable}>
     */
    private const CONFIG_MAP = [
        'app_name' => ['path' => 'app.name'],
        'app_timezone' => ['path' => 'app.timezone'],
        'app_locale' => ['path' => 'app.locale'],
        'app_fallback_locale' => ['path' => 'app.fallback_locale'],
        'mail_from_address' => ['path' => 'mail.from.address'],
        'mail_from_name' => ['path' => 'mail.from.name'],
        'session_lifetime' => ['path' => 'session.lifetime', 'transform' => 'intval'],
        'session_expire_on_close' => ['path' => 'session.expire_on_close', 'transform' => 'boolval'],
    ];

    /**
     * Sync application configuration from settings.
     *
     * Updates application and mail settings from the settings system.
     * Safely handles cases where settings table doesn't exist (during migrations).
     * Logs errors at debug level for troubleshooting.
     *
     * @return void
     */
    public function syncFromSettings(): void
    {
        if (! function_exists('settings') || is_running_migrations()) {
            return;
        }

        try {
            $appName = settings('app_name', config('app.name'));
            config(['app.name' => $appName]);

            $appTimezone = settings('app_timezone', config('app.timezone'));
            config(['app.timezone' => $appTimezone]);

            $appLocale = settings('app_locale', config('app.locale'));
            config(['app.locale' => $appLocale]);
            Carbon::setLocale($appLocale);

            $appFallbackLocale = settings('app_fallback_locale', config('app.fallback_locale'));
            config(['app.fallback_locale' => $appFallbackLocale]);

            $mailFromAddress = settings('mail_from_address', config('mail.from.address'));
            $mailFromName = settings('mail_from_name', config('mail.from.name'));
            config([
                'mail.from.address' => $mailFromAddress,
                'mail.from.name' => $mailFromName,
            ]);
        } catch (\Throwable $e) {
            Log::debug('AppConfigService: Failed to sync settings from database', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync a single setting key to its corresponding config value.
     *
     * Used by SettingsService when a setting is updated to immediately
     * reflect the change in Laravel's config. Only syncs keys that have
     * corresponding config paths defined in CONFIG_MAP.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $value  The new value
     * @return bool True if the key was synced, false if not applicable
     */
    public function syncKey(string $key, mixed $value): bool
    {
        if (! isset(self::CONFIG_MAP[$key])) {
            return false;
        }

        $mapping = self::CONFIG_MAP[$key];
        $path = $mapping['path'];
        $transform = $mapping['transform'] ?? null;

        $transformedValue = $transform ? $transform($value) : $value;

        if (is_array($path)) {
            foreach ($path as $p) {
                config([$p => $transformedValue]);
            }
        } else {
            config([$path => $transformedValue]);
        }

        if ($key === 'app_locale') {
            Carbon::setLocale((string) $transformedValue);
        }

        return true;
    }
}

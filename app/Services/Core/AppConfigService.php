<?php

namespace App\Services\Core;

/**
 * Service for synchronizing application configuration from settings.
 *
 * Centralizes the logic for updating Laravel config values from the
 * settings system, ensuring proper handling during migrations.
 */
class AppConfigService
{
    /**
     * Sync application configuration from settings.
     *
     * Updates application and mail settings from the settings system.
     * Safely handles cases where settings table doesn't exist (during migrations).
     * Silently fails if settings table doesn't exist or other errors occur.
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

            $appFallbackLocale = settings('app_fallback_locale', config('app.fallback_locale'));
            config(['app.fallback_locale' => $appFallbackLocale]);

            $mailFromAddress = settings('mail_from_address', config('mail.from.address'));
            $mailFromName = settings('mail_from_name', config('mail.from.name'));
            config([
                'mail.from.address' => $mailFromAddress,
                'mail.from.name' => $mailFromName,
            ]);
        } catch (\Throwable $e) {
        }
    }
}

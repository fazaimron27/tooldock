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
     * Updates app.name and app.debug from the settings system.
     * Safely handles cases where settings table doesn't exist (during migrations).
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

            $appDebug = settings('app_debug', config('app.debug'));
            config(['app.debug' => filter_var($appDebug, FILTER_VALIDATE_BOOLEAN)]);
        } catch (\Throwable $e) {
            // Silently fail if settings table doesn't exist or other errors occur
        }
    }
}

<?php

use App\Services\Core\SettingsService;

if (! function_exists('settings')) {
    /**
     * Get a setting value or return the SettingsService instance.
     *
     * Usage:
     * - settings('app_name') - Get a specific setting value
     * - settings('app_name', 'Default') - Get a setting with default value
     * - settings() - Get the SettingsService instance for advanced usage
     *
     * @param  string|null  $key  The setting key
     * @param  mixed  $default  Default value if key not found
     * @return mixed|\App\Services\Core\SettingsService
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $service = app(SettingsService::class);

        if ($key === null) {
            return $service;
        }

        return $service->get($key, $default);
    }
}

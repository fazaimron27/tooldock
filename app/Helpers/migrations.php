<?php

/**
 * Migration Helpers.
 *
 * Global helper functions for detecting if the application is currently
 * running database migrations. Used to guard logic that should only
 * execute outside of migration contexts.
 *
 * @author Tool Dock Team
 * @license MIT
 */
if (! function_exists('is_running_migrations')) {
    /**
     * Check if we're currently running migrations.
     *
     * @return bool
     */
    function is_running_migrations(): bool
    {
        if (! app()->runningInConsole()) {
            return false;
        }

        $command = $_SERVER['argv'][1] ?? '';

        return str_contains($command, 'migrate');
    }
}

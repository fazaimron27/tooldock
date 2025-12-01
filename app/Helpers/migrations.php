<?php

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

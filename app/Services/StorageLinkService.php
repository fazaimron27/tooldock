<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Service for managing the storage symbolic link.
 *
 * Ensures the public storage link exists automatically,
 * making public files accessible without manual intervention.
 */
class StorageLinkService
{
    /**
     * Ensure the storage symbolic link exists.
     *
     * Creates the storage link automatically if it doesn't exist.
     * This ensures public files are accessible without manual intervention.
     *
     * @return void
     */
    public function ensureExists(): void
    {
        $linkPath = public_path('storage');
        $targetPath = storage_path('app/public');

        if (! file_exists($linkPath) && ! is_running_migrations()) {
            try {
                if (! File::exists($targetPath)) {
                    File::makeDirectory($targetPath, 0755, true);
                }

                if (app()->runningInConsole()) {
                    Artisan::call('storage:link');
                } else {
                    if (function_exists('symlink')) {
                        symlink($targetPath, $linkPath);
                    }
                }
            } catch (\Exception $e) {
                // Silently fail if symlink creation fails
            }
        }
    }
}

<?php

namespace App\Services;

/**
 * Service for managing media configuration, particularly file size limits.
 *
 * Centralizes all media/file size configuration logic that can be reused
 * across middleware, exception handlers, and other parts of the application.
 */
class MediaConfigService
{
    /**
     * Get the effective maximum file size information.
     *
     * Returns comprehensive file size information including effective limits,
     * PHP limits, and whether PHP limits are restricting the configured limit.
     *
     * @param  int|null  $configuredMax  Optional configured max file size in KB. If null, reads from settings.
     * @return array{effective_kb: int, effective_mb: float, is_php_limited: bool, php_limit_mb: float, php_upload_max_kb: int, php_post_max_kb: int}
     */
    public function getFileSizeInfo(?int $configuredMax = null): array
    {
        if ($configuredMax === null) {
            $configuredMax = (int) settings('max_file_size', 10240);
        }

        return get_effective_max_file_size($configuredMax);
    }

    /**
     * Get the error message for a 413 (File too large) error.
     *
     * Generates an appropriate error message based on whether PHP limits
     * are restricting the file size or if it's a configured limit issue.
     *
     * @param  int|null  $configuredMax  Optional configured max file size in KB. If null, reads from settings.
     * @return string The error message
     */
    public function getFileTooLargeErrorMessage(?int $configuredMax = null): string
    {
        $fileSizeInfo = $this->getFileSizeInfo($configuredMax);

        if ($fileSizeInfo['is_php_limited']) {
            return "File size exceeds the server PHP limit of {$fileSizeInfo['php_limit_mb']}MB. Please contact your administrator to increase PHP upload_max_filesize and post_max_size settings.";
        }

        return "File size exceeds the maximum allowed size of {$fileSizeInfo['effective_mb']}MB. If you've increased PHP limits, you may also need to increase web server limits (Nginx: client_max_body_size, Apache: LimitRequestBody).";
    }
}

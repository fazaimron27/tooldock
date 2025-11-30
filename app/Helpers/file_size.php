<?php

if (! function_exists('convert_size_to_kb')) {
    /**
     * Convert PHP ini size value (e.g., "2M", "8M", "128K") to KB.
     *
     * @param  string  $size  The size string from PHP ini (e.g., "2M", "128K")
     * @return int Size in KB
     *
     * @throws \InvalidArgumentException If the size string is invalid
     */
    function convert_size_to_kb(string $size): int
    {
        $size = trim($size);

        if ($size === '') {
            throw new \InvalidArgumentException('Size string cannot be empty.');
        }

        $unit = strtolower(substr($size, -1));
        $value = (int) $size;

        if ($value <= 0 && $unit !== 'k' && $unit !== 'm' && $unit !== 'g') {
            throw new \InvalidArgumentException("Invalid size format: '{$size}'. Expected format: number followed by K, M, or G (e.g., '2M', '128K').");
        }

        return match ($unit) {
            'g' => $value * 1024 * 1024,
            'm' => $value * 1024,
            'k' => $value,
            default => (int) $size, // Assume bytes, convert to KB
        };
    }
}

if (! function_exists('get_php_upload_limits')) {
    /**
     * Get PHP upload limits in KB.
     *
     * @return array{upload_max: int, post_max: int, effective: int}
     */
    function get_php_upload_limits(): array
    {
        $uploadMax = convert_size_to_kb(ini_get('upload_max_filesize'));
        $postMax = convert_size_to_kb(ini_get('post_max_size'));

        return [
            'upload_max' => $uploadMax,
            'post_max' => $postMax,
            'effective' => min($uploadMax, $postMax),
        ];
    }
}

if (! function_exists('get_effective_max_file_size')) {
    /**
     * Get the effective maximum file size considering both settings and PHP limits.
     *
     * @param  int|null  $configuredMax  The configured max file size in KB (from settings)
     * @return array{effective_kb: int, effective_mb: float, is_php_limited: bool, php_limit_mb: float}
     *
     * @throws \InvalidArgumentException If the configured max is negative
     */
    function get_effective_max_file_size(?int $configuredMax = null): array
    {
        $configuredMax = $configuredMax ?? (int) settings('max_file_size', 10240);

        if ($configuredMax < 0) {
            throw new \InvalidArgumentException("Configured max file size cannot be negative. Got: {$configuredMax} KB.");
        }

        $phpLimits = get_php_upload_limits();
        $effectiveKB = min($configuredMax, $phpLimits['effective']);

        return [
            'effective_kb' => $effectiveKB,
            'effective_mb' => round($effectiveKB / 1024, 1),
            'is_php_limited' => $phpLimits['effective'] < $configuredMax,
            'php_limit_mb' => round($phpLimits['effective'] / 1024, 1),
            'php_upload_max_kb' => $phpLimits['upload_max'],
            'php_post_max_kb' => $phpLimits['post_max'],
        ];
    }
}

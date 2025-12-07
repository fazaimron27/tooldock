<?php

namespace Modules\AuditLog\App\Services;

use Carbon\Carbon;

/**
 * Trait providing helper methods for formatting audit log values.
 *
 * Contains shared formatting utilities used across all audit log formatters.
 */
trait AuditLogFormattingHelper
{
    /**
     * Format file size in human-readable format.
     *
     * @param  int  $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Format a field name for human readability.
     *
     * @param  string  $key
     * @return string
     */
    protected function formatFieldName(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Format a value for human-readable display.
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function formatValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('F j, Y \a\t g:i A');
        }

        $stringValue = (string) $value;

        if ($this->isDateString($stringValue)) {
            try {
                $date = Carbon::parse($stringValue);

                return $date->format('F j, Y \a\t g:i A');
            } catch (\Exception) {
                // Date parsing failed, continue to default string formatting
            }
        }

        if (strlen($stringValue) > 100) {
            return substr($stringValue, 0, 100).'...';
        }

        return "'{$stringValue}'";
    }

    /**
     * Check if a string value matches a date pattern.
     *
     * Uses Carbon to attempt parsing, which is more reliable than regex patterns.
     * Carbon can handle many date formats including ISO, MySQL datetime, and more.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isDateString(string $value): bool
    {
        $length = strlen($value);
        if ($length < 8 || $length > 50) {
            return false;
        }

        if (! preg_match('/\d{4}/', $value)) {
            return false;
        }

        try {
            $date = Carbon::parse($value);
            $year = $date->year;

            return $year >= 1000 && $year <= 9999;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Ensure a value is an array.
     *
     * Handles cases where the value might be a JSON string or null.
     *
     * @param  mixed  $value
     * @return array<string, mixed>
     */
    protected function ensureArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}

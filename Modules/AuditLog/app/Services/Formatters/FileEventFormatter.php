<?php

namespace Modules\AuditLog\App\Services\Formatters;

use Carbon\Carbon;

/**
 * Formatter for file-related events.
 *
 * Handles: file_uploaded, file_deleted
 */
class FileEventFormatter extends AuditLogFormatter
{
    /**
     * Format the diff based on event type.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  string|null  $event
     * @return array<string>
     */
    public function format(array $oldValues, array $newValues, ?string $event = null): array
    {
        if (! $event) {
            return [];
        }

        return match ($event) {
            'file_uploaded' => $this->formatFileUploadedDiff($newValues),
            'file_deleted' => $this->formatFileDeletedDiff($oldValues),
            default => [],
        };
    }

    /**
     * Format diff for file uploaded events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatFileUploadedDiff(array $newValues): array
    {
        $changes = [];
        $filename = $newValues['filename'] ?? null;
        $mimeType = $newValues['mime_type'] ?? null;
        $size = $newValues['size'] ?? null;
        $isTemporary = $newValues['is_temporary'] ?? false;
        $uploadedAt = $newValues['created_at'] ?? null;

        if ($filename) {
            $fileType = $isTemporary ? 'temporary' : 'permanent';
            $changes[] = "File '{$filename}' uploaded ({$fileType})";
        } else {
            $changes[] = 'File uploaded';
        }

        if ($mimeType) {
            $changes[] = "MIME type: {$mimeType}";
        }

        if ($size !== null) {
            $formattedSize = $this->formatFileSize($size);
            $changes[] = "Size: {$formattedSize}";
        }

        if ($uploadedAt) {
            try {
                $date = Carbon::parse($uploadedAt);
                $changes[] = "Uploaded at: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for file deleted events.
     *
     * @param  array<string, mixed>  $oldValues
     * @return array<string>
     */
    protected function formatFileDeletedDiff(array $oldValues): array
    {
        $changes = [];
        $filename = $oldValues['filename'] ?? null;
        $mimeType = $oldValues['mime_type'] ?? null;
        $size = $oldValues['size'] ?? null;
        $deletedAt = $oldValues['deleted_at'] ?? now()->toIso8601String();

        if ($filename) {
            $changes[] = "File '{$filename}' deleted";
        } else {
            $changes[] = 'File deleted';
        }

        if ($mimeType) {
            $changes[] = "MIME type: {$mimeType}";
        }

        if ($size !== null) {
            $formattedSize = $this->formatFileSize($size);
            $changes[] = "Size: {$formattedSize}";
        }

        if ($deletedAt) {
            try {
                $date = Carbon::parse($deletedAt);
                $changes[] = "Deleted at: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }
}

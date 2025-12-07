<?php

namespace Modules\AuditLog\Services\Formatters;

/**
 * Formatter for generic CRUD events (created, updated, deleted, export).
 */
class GenericEventFormatter extends AuditLogFormatter
{
    /**
     * Format diff for created events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatCreatedDiff(array $newValues): array
    {
        $changes = [];

        foreach ($newValues as $key => $value) {
            $fieldName = $this->formatFieldName($key);
            $formattedValue = $this->formatValue($value);

            if ($formattedValue !== null) {
                $changes[] = "Added {$fieldName}: {$formattedValue}";
            } else {
                $changes[] = "Added {$fieldName}";
            }
        }

        return $changes;
    }

    /**
     * Format diff for deleted events.
     *
     * @param  array<string, mixed>  $oldValues
     * @return array<string>
     */
    protected function formatDeletedDiff(array $oldValues): array
    {
        $changes = [];

        foreach ($oldValues as $key => $value) {
            $fieldName = $this->formatFieldName($key);
            $formattedValue = $this->formatValue($value);

            if ($formattedValue !== null) {
                $changes[] = "Removed {$fieldName}: {$formattedValue}";
            } else {
                $changes[] = "Removed {$fieldName}";
            }
        }

        return $changes;
    }

    /**
     * Format diff for updated events.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatUpdatedDiff(array $oldValues, array $newValues): array
    {
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            // Skip if values are the same
            if ($oldValue === $newValue) {
                continue;
            }

            $fieldName = $this->formatFieldName($key);
            $formattedOldValue = $this->formatValue($oldValue);
            $formattedNewValue = $this->formatValue($newValue);

            if ($formattedOldValue === null && $formattedNewValue !== null) {
                $changes[] = "Set {$fieldName} to {$formattedNewValue}";
            } elseif ($formattedOldValue !== null && $formattedNewValue === null) {
                $changes[] = "Removed {$fieldName} (was {$formattedOldValue})";
            } else {
                $changes[] = "Changed {$fieldName} from {$formattedOldValue} to {$formattedNewValue}";
            }
        }

        return $changes;
    }

    /**
     * Format diff for export events.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  string  $event
     * @return array<string>
     */
    public function format(array $oldValues, array $newValues, ?string $event = null): array
    {
        // Handle export events specially
        if ($event === 'export') {
            return $this->formatExportDiff($newValues);
        }

        // For created events, only newValues are present
        if (empty($oldValues) && ! empty($newValues)) {
            return $this->formatCreatedDiff($newValues);
        }

        // For deleted events, only oldValues are present
        if (! empty($oldValues) && empty($newValues)) {
            return $this->formatDeletedDiff($oldValues);
        }

        // For updated events, both are present
        if (! empty($oldValues) && ! empty($newValues)) {
            return $this->formatUpdatedDiff($oldValues, $newValues);
        }

        return [];
    }

    /**
     * Format diff for export events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatExportDiff(array $newValues): array
    {
        $changes = [];
        $format = $newValues['format'] ?? 'CSV';
        $recordCount = $newValues['record_count'] ?? null;
        $exportedAt = $newValues['exported_at'] ?? null;

        $changes[] = "Exported audit logs as {$format}";

        if ($recordCount !== null) {
            $changes[] = "Exported {$recordCount} record".($recordCount === 1 ? '' : 's');
        }

        if ($exportedAt) {
            try {
                $date = \Carbon\Carbon::parse($exportedAt);
                $changes[] = "Export time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }
}

<?php

namespace Modules\AuditLog\App\Services\Formatters;

/**
 * Formatter for relationship synchronization events.
 *
 * Handles: relationship_synced
 */
class RelationshipEventFormatter extends AuditLogFormatter
{
    /**
     * Format the diff for relationship synced events.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  string|null  $event
     * @return array<string>
     */
    public function format(array $oldValues, array $newValues, ?string $event = null): array
    {
        return $this->formatRelationshipSyncedDiff($oldValues, $newValues);
    }

    /**
     * Format diff for relationship synced events.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatRelationshipSyncedDiff(array $oldValues, array $newValues): array
    {
        $changes = [];

        // Find relationship name from keys (e.g., 'roles', 'permissions', 'members')
        $relationshipKeys = array_filter(
            array_keys($newValues),
            fn ($key) => ! str_ends_with($key, '_ids')
        );

        foreach ($relationshipKeys as $relationshipName) {
            $oldNames = $oldValues[$relationshipName] ?? [];
            $newNames = $newValues[$relationshipName] ?? [];
            $oldIds = $oldValues[$relationshipName.'_ids'] ?? [];
            $newIds = $newValues[$relationshipName.'_ids'] ?? [];

            // Ensure arrays for consistent comparison
            $oldIds = is_array($oldIds) ? array_map('strval', $oldIds) : [];
            $newIds = is_array($newIds) ? array_map('strval', $newIds) : [];

            if ($oldIds === $newIds) {
                continue;
            }

            $relationshipDisplayName = ucwords(str_replace(['_', '-'], ' ', $relationshipName));

            $added = array_diff($newIds, $oldIds);
            $removed = array_diff($oldIds, $newIds);

            if (! empty($added)) {
                $addedNames = [];
                foreach ($added as $id) {
                    if (isset($newNames[$id])) {
                        $addedNames[] = $newNames[$id];
                    }
                }
                if (! empty($addedNames)) {
                    $changes[] = "Added {$relationshipDisplayName}: ".implode(', ', $addedNames);
                } else {
                    $changes[] = "Added {$relationshipDisplayName}: ".count($added).' item(s)';
                }
            }

            if (! empty($removed)) {
                $removedNames = [];
                foreach ($removed as $id) {
                    if (isset($oldNames[$id])) {
                        $removedNames[] = $oldNames[$id];
                    }
                }
                if (! empty($removedNames)) {
                    $changes[] = "Removed {$relationshipDisplayName}: ".implode(', ', $removedNames);
                } else {
                    $changes[] = "Removed {$relationshipDisplayName}: ".count($removed).' item(s)';
                }
            }
        }

        if (empty($changes)) {
            $changes[] = 'Relationship synchronized';
        }

        return $changes;
    }
}

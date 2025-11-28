<?php

namespace Modules\Core\App\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

/**
 * Service for managing and organizing permissions.
 *
 * Provides utility methods for grouping and formatting permissions
 * in a consistent way across the application.
 */
class PermissionService
{
    /**
     * Group permissions by their prefix (resource name).
     *
     * Permissions follow pattern: {action} {resource}
     * Example: 'view users' -> group: 'users'
     *          'create posts' -> group: 'posts'
     *
     * @param  Collection<int, Permission>  $permissions
     * @return array<string, array<int, array{id: int, name: string}>>
     */
    public function groupByPrefix(Collection $permissions): array
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode(' ', $permission->name, 2);
            $group = count($parts) > 1 ? $parts[1] : 'other';

            if (! isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            $grouped[$group][] = [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        }

        // Sort groups alphabetically
        ksort($grouped);

        return $grouped;
    }
}

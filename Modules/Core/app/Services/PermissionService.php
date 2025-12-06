<?php

namespace Modules\Core\App\Services;

use Illuminate\Support\Collection;
use Modules\Core\App\Models\Permission;

/**
 * Service for managing and organizing permissions.
 *
 * Provides utility methods for grouping and formatting permissions
 * in a consistent way across the application.
 */
class PermissionService
{
    /**
     * Group permissions by module, then by resource.
     *
     * Permissions follow pattern: {module}.{resource}.{action}
     * Example: 'blog.posts.view' -> module: 'blog', resource: 'posts'
     *          'core.users.edit' -> module: 'core', resource: 'users'
     *
     * @param  Collection<int, Permission>  $permissions
     * @return array<string, array<string, array<int, array{id: int, name: string, action: string}>>>
     */
    public function groupByModule(Collection $permissions): array
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'other';
            $resource = $parts[1] ?? 'other';
            $action = $parts[2] ?? 'other';

            if (! isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            if (! isset($grouped[$module][$resource])) {
                $grouped[$module][$resource] = [];
            }

            $grouped[$module][$resource][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'action' => $action,
            ];
        }

        ksort($grouped);
        foreach ($grouped as $module => $resources) {
            ksort($grouped[$module]);
        }

        return $grouped;
    }
}

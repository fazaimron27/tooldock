<?php

namespace Modules\Blog\Services;

use App\Services\Registry\PermissionRegistry;

/**
 * Handles permission registration for the Blog module.
 */
class BlogPermissionRegistrar
{
    /**
     * Register default permissions for the Blog module.
     */
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->register('blog', [
            'dashboard.view',
            'posts.view',
            'posts.create',
            'posts.edit',
            'posts.delete',
            'posts.publish',
        ], [
            'Administrator' => ['dashboard.view', 'posts.*'],
            'Staff' => ['dashboard.view', 'posts.view', 'posts.create'],
        ]);
    }
}

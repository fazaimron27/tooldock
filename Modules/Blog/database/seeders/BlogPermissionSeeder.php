<?php

namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Services\PermissionRegistry;

class BlogPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->register('blog', [
            'posts.view',
            'posts.create',
            'posts.edit',
            'posts.delete',
            'posts.publish',
        ], [
            'Administrator' => ['posts.*'],
            'Staff' => ['posts.view', 'posts.create'],
        ]);
    }
}

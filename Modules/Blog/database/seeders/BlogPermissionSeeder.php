<?php

namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BlogPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create blog permissions
        $permissions = [
            'view posts',
            'create posts',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Find existing Administrator role and give it all blog permissions
        $administratorRole = Role::where('name', 'Administrator')->first();
        if ($administratorRole) {
            $administratorRole->givePermissionTo(['view posts', 'create posts']);
        }

        // Find existing Staff role and give it only create posts
        $staffRole = Role::where('name', 'Staff')->first();
        if ($staffRole) {
            $staffRole->givePermissionTo('create posts');
        }
    }
}

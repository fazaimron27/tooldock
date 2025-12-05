<?php

namespace Modules\Groups\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Models\User;
use Modules\Groups\Models\Group;
use Spatie\Permission\Models\Permission;

class GroupsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates test/demo data: an "Editors" group with blog.posts.create permission,
     * and a test user "John" assigned to the group with no roles.
     *
     * Note: The "Guest" group is automatically created via GroupRegistry during
     * module installation/enabling, so it's not created here.
     */
    public function run(): void
    {
        // Create or get the "Editors" group (test/demo group)
        $editorsGroup = Group::firstOrCreate(
            ['slug' => 'editors'],
            [
                'name' => 'Editors',
                'description' => 'Group for content editors who can create blog posts',
            ]
        );

        // Assign blog.posts.create permission to the group
        $permission = Permission::where('name', 'blog.posts.create')->first();
        if ($permission && ! $editorsGroup->permissions()->where('permissions.id', $permission->id)->exists()) {
            $editorsGroup->permissions()->attach($permission->id);
        }

        // Create or get user "John" with no roles
        $john = User::withoutEvents(function () {
            return User::firstOrCreate(
                ['email' => 'john@example.com'],
                [
                    'name' => 'John',
                    'email' => 'john@example.com',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        });

        // Ensure John has no roles
        $john->roles()->detach();

        // Assign John to the Editors group
        if (! $editorsGroup->users()->where('users.id', $john->id)->exists()) {
            $editorsGroup->users()->attach($john->id);
        }
    }
}

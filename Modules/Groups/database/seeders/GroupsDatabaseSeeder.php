<?php

namespace Modules\Groups\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Models\User;
use Modules\Groups\Models\Group;

class GroupsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates test/demo data: an "Editors" group and a test user "John" assigned to the group with no roles.
     *
     * Note: The "Guest" group is automatically created via GroupRegistry during
     * module installation/enabling, so it's not created here.
     */
    public function run(): void
    {
        $editorsGroup = Group::firstOrCreate(
            ['slug' => 'editors'],
            [
                'name' => 'Editors',
                'description' => 'Group for content editors',
            ]
        );

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

        $john->roles()->detach();

        if (! $editorsGroup->users()->where('users.id', $john->id)->exists()) {
            $editorsGroup->users()->attach($john->id);
        }
    }
}

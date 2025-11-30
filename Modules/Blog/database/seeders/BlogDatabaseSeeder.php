<?php

namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Blog\Models\Post;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\User;

class BlogDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: BlogPermissionSeeder is automatically run during module installation.
     * This seeder only creates sample data for development/testing.
     */
    public function run(): void
    {
        $user = User::withoutEvents(function () {
            return User::firstOrCreate(
                ['email' => 'author@example.com'],
                array_merge(
                    User::factory()->make()->getAttributes(),
                    [
                        'name' => 'Author',
                        'email' => 'author@example.com',
                        'password' => 'password',
                        'email_verified_at' => now(),
                    ]
                )
            );
        });

        if (! $user->roles()->exists()) {
            $user->assignRole(Roles::STAFF);
        }

        Post::factory(10)->published()->create([
            'user_id' => $user->id,
        ]);

        Post::factory(3)->draft()->create([
            'user_id' => $user->id,
        ]);
    }
}

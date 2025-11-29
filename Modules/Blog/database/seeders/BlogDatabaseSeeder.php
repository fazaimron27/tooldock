<?php

namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Blog\Models\Post;
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
        // Get or create a user for blog posts
        $user = User::firstOrCreate(
            ['email' => 'author@example.com'],
            [
                'name' => 'Blog Author',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        // Create published posts
        Post::factory(10)->published()->create([
            'user_id' => $user->id,
        ]);

        // Create draft posts
        Post::factory(3)->draft()->create([
            'user_id' => $user->id,
        ]);
    }
}

<?php

namespace Modules\Blog\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Blog\Models\Post;

class BlogDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
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

<?php

namespace Modules\Newsletter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Models\User;
use Modules\Newsletter\Models\Campaign;

class NewsletterDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: NewsletterPermissionSeeder is automatically run during module installation.
     * This seeder only creates sample data for development/testing.
     */
    public function run(): void
    {
        // Get or create a user for the campaign
        $user = User::first();
        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Newsletter User',
                'email' => 'newsletter@example.com',
            ]);
        }

        // Get some published post IDs from Blog module if available
        $postIds = [];
        if (class_exists(\Modules\Blog\Models\Post::class)) {
            $postIds = \Modules\Blog\Models\Post::query()
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->limit(3)
                ->pluck('id')
                ->toArray();
        }

        // Create a sample draft campaign
        Campaign::create([
            'user_id' => $user->id,
            'subject' => 'Weekly Newsletter - Sample Campaign',
            'status' => 'draft',
            'content' => 'This is a sample newsletter campaign. You can customize the content and select blog posts to include in your email.',
            'selected_posts' => $postIds,
            'scheduled_at' => null,
        ]);
    }
}

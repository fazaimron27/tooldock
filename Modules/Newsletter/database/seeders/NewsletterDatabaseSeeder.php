<?php

namespace Modules\Newsletter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Blog\Models\Post;
use Modules\Core\App\Constants\Roles;
use Modules\Core\App\Models\User;
use Modules\Newsletter\Models\Campaign;
use Nwidart\Modules\Facades\Module;

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
        $user = User::withoutEvents(function () {
            return User::firstOrCreate(
                ['email' => 'newsletter@example.com'],
                array_merge(
                    User::factory()->make()->getAttributes(),
                    [
                        'name' => 'Newsletter',
                        'email' => 'newsletter@example.com',
                        'password' => 'password',
                        'email_verified_at' => now(),
                    ]
                )
            );
        });

        if (! $user->roles()->exists()) {
            $user->assignRole(Roles::STAFF);
        }

        $postIds = [];
        if (Module::has('Blog') && Module::isEnabled('Blog')) {
            $posts = Post::factory(3)->published()->create([
                'user_id' => $user->id,
            ]);

            $postIds = $posts->pluck('id')->toArray();
        }

        Campaign::create([
            'user_id' => $user->id,
            'subject' => 'Weekly Newsletter - Sample Campaign',
            'status' => 'draft',
            'content' => 'This is a sample newsletter campaign. You can customize the content and select your own blog posts to include in your email.',
            'selected_posts' => $postIds,
            'scheduled_at' => null,
        ]);
    }
}

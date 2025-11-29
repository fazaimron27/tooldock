<?php

namespace Modules\Newsletter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\App\Services\PermissionRegistry;

class NewsletterPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistry::class)->register('newsletter', [
            'campaigns.view',
            'campaigns.create',
            'campaigns.edit',
            'campaigns.delete',
            'campaigns.send',
        ], [
            'Administrator' => ['campaigns.*'],
            'Staff' => ['campaigns.view', 'campaigns.create'],
        ]);
    }
}

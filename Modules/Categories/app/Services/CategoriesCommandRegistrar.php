<?php

namespace Modules\Categories\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Categories module.
 */
class CategoriesCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Categories module.
     */
    public function register(CommandRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'Master Data', [
            [
                'label' => 'Categories',
                'route' => 'categories.index',
                'icon' => 'tag',
                'permission' => 'categories.category.view',
                'keywords' => ['category', 'tag', 'classify', 'organize'],
                'order' => 30,
            ],
        ]);
    }
}

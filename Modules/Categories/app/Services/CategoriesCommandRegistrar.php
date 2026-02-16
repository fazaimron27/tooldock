<?php

/**
 * Categories Command Registrar.
 *
 * Registers Command Palette commands for the Categories module.
 *
 * @author Tool Dock Team
 * @license MIT
 */

namespace Modules\Categories\Services;

use App\Services\Registry\CommandRegistry;

/**
 * Registers Command Palette commands for the Categories module.
 */
class CategoriesCommandRegistrar
{
    /**
     * Register all Command Palette commands for the Categories module.
     *
     * @param  CommandRegistry  $registry  The command palette registry
     * @param  string  $moduleName  The module name
     * @return void
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
